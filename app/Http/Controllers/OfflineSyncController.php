<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityLogRequest;
use App\Http\Requests\StoreDtrRequest;
use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use App\Services\DtrEntryService;
use App\Services\EntryFingerprint;
use App\Services\WeeklySubmissionLockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;

class OfflineSyncController extends Controller
{
    public function storeDtr(
        Request $request,
        EntryFingerprint $fingerprint,
        DtrEntryService $dtrEntries,
        WeeklySubmissionLockService $weeklySubmissionLocks,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        Gate::authorize('create', DtrEntry::class);
        $validator = Validator::make(
            $request->all(),
            StoreDtrRequest::rulesFor($user->id, enforceUniqueDate: false, requireIdempotencyKey: true),
        );
        $validator->after(function (ValidationValidator $validator) use ($request): void {
            StoreDtrRequest::addChronologyErrors($validator, $request->all());
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => 'Review the pending DTR draft and correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $requestFingerprint = $fingerprint->dtr($data);
        $existingByKey = DtrEntry::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existingByKey !== null) {
            return $this->dtrReplayResponse($existingByKey, $requestFingerprint);
        }

        $existingByDate = DtrEntry::query()
            ->whereBelongsTo($user)
            ->where('work_date', $data['work_date'])
            ->first();

        if ($existingByDate !== null) {
            return $this->dtrDateConflict($existingByDate);
        }

        if ($weeklySubmissionLocks->isDateLocked($user, $data['work_date'])) {
            return $this->dtrWeekLockedConflict();
        }

        try {
            $entry = $dtrEntries->create($user, $data);
        } catch (ValidationException) {
            return $this->dtrWeekLockedConflict();
        } catch (QueryException $exception) {
            $existingByKey = DtrEntry::query()
                ->whereBelongsTo($user)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existingByKey !== null) {
                return $this->dtrReplayResponse($existingByKey, $requestFingerprint);
            }

            $existingByDate = DtrEntry::query()
                ->whereBelongsTo($user)
                ->where('work_date', $data['work_date'])
                ->first();

            if ($existingByDate !== null) {
                return $this->dtrDateConflict($existingByDate);
            }

            throw $exception;
        }

        return response()->json([
            'status' => 'synced',
            'message' => 'DTR draft synced. Official hours were calculated by the server.',
            'record' => [
                'id' => $entry->id,
                'work_date' => $entry->work_date->toDateString(),
                'total_minutes' => $entry->total_minutes,
            ],
        ], 201);
    }

    public function storeActivity(Request $request, EntryFingerprint $fingerprint): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        Gate::authorize('create', ActivityLog::class);
        $validator = Validator::make($request->all(), StoreActivityLogRequest::rulesFor(
            offlineSync: true,
            userId: $user->id,
        ));

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => 'Review the pending activity draft and correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $requestFingerprint = $fingerprint->activity($data);
        $existing = ActivityLog::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existing !== null) {
            return $this->activityReplayResponse($existing, $requestFingerprint);
        }

        try {
            $activity = $user->activityLogs()->create([
                'activity_date' => $data['activity_date'],
                'tasks_completed' => $data['tasks_completed'],
                'skills_tools' => $data['skills_tools'] ?? null,
                'lessons_learned' => $data['lessons_learned'] ?? null,
                'challenges_notes' => $data['challenges_notes'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
                'request_fingerprint' => $requestFingerprint,
            ]);
        } catch (QueryException $exception) {
            $existing = ActivityLog::query()
                ->whereBelongsTo($user)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing !== null) {
                return $this->activityReplayResponse($existing, $requestFingerprint);
            }

            throw $exception;
        }

        return response()->json([
            'status' => 'synced',
            'message' => 'Activity draft synced.',
            'record' => [
                'id' => $activity->id,
                'activity_date' => $activity->activity_date->toDateString(),
            ],
        ], 201);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    private function dtrReplayResponse(DtrEntry $entry, string $requestFingerprint): JsonResponse
    {
        if (! hash_equals($entry->request_fingerprint, $requestFingerprint)) {
            return response()->json([
                'status' => 'conflict',
                'conflict_type' => 'idempotency_key',
                'message' => 'This draft key was already used for different DTR details. Keep the draft pending, review it, and save it with a new key.',
            ], 409);
        }

        return response()->json([
            'status' => 'synced',
            'message' => 'This DTR draft was already synced.',
            'record' => [
                'id' => $entry->id,
                'work_date' => $entry->work_date->toDateString(),
                'total_minutes' => $entry->total_minutes,
            ],
        ]);
    }

    private function dtrDateConflict(DtrEntry $entry): JsonResponse
    {
        return response()->json([
            'status' => 'conflict',
            'conflict_type' => 'work_date',
            'message' => 'A saved DTR already exists for this date. Review the saved entry, then edit or discard this pending draft.',
            'existing_record_id' => $entry->id,
            'resolution_url' => route('dtr.edit', $entry),
        ], 409);
    }

    private function dtrWeekLockedConflict(): JsonResponse
    {
        return response()->json([
            'status' => 'conflict',
            'conflict_type' => 'weekly_submission',
            'message' => 'This week is already submitted or approved. Keep the draft pending and request changes before adding another DTR.',
            'resolution_url' => route('submissions.index'),
        ], 409);
    }

    private function activityReplayResponse(ActivityLog $activity, string $requestFingerprint): JsonResponse
    {
        if (! hash_equals($activity->request_fingerprint, $requestFingerprint)) {
            return response()->json([
                'status' => 'conflict',
                'conflict_type' => 'idempotency_key',
                'message' => 'This draft key was already used for different activity details. Keep the draft pending, review it, and save it with a new key.',
            ], 409);
        }

        return response()->json([
            'status' => 'synced',
            'message' => 'This activity draft was already synced.',
            'record' => [
                'id' => $activity->id,
                'activity_date' => $activity->activity_date->toDateString(),
            ],
        ]);
    }
}
