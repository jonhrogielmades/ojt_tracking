<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityLogRequest;
use App\Http\Requests\UpdateActivityLogRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\EntryFingerprint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ActivityLog::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $activities = ActivityLog::query()
            ->whereBelongsTo($user)
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('tasks_completed', 'like', "%{$search}%")
                        ->orWhere('skills_tools', 'like', "%{$search}%")
                        ->orWhere('lessons_learned', 'like', "%{$search}%")
                        ->orWhere('challenges_notes', 'like', "%{$search}%");
                });
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from): Builder => $query->whereDate('activity_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to): Builder => $query->whereDate('activity_date', '<=', $to))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('activities.index', compact('activities', 'filters'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ActivityLog::class);

        return view('activities.create', ['defaultDate' => now()->toDateString()]);
    }

    public function store(StoreActivityLogRequest $request, EntryFingerprint $fingerprint): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', ActivityLog::class);
        /** @var User $user */
        $user = $request->user();
        $data = $request->safe()->only([
            'activity_date',
            'tasks_completed',
            'skills_tools',
            'lessons_learned',
            'challenges_notes',
            'idempotency_key',
        ]);
        $data['idempotency_key'] = $data['idempotency_key'] ?? Str::uuid()->toString();
        $data['request_fingerprint'] = $fingerprint->activity($data);
        $existingActivity = ActivityLog::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existingActivity !== null) {
            return $this->activityReplayResponse($request, $existingActivity, $data['request_fingerprint']);
        }

        $newAttachmentPath = null;
        $attachmentDisk = $this->attachmentDisk();

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $newAttachmentPath = $attachment->store("activity-attachments/{$user->id}", $attachmentDisk);
            $data += [
                'attachment_path' => $newAttachmentPath,
                'attachment_original_name' => $attachment->getClientOriginalName(),
                'attachment_mime_type' => $attachment->getMimeType(),
                'attachment_size' => $attachment->getSize(),
            ];
        }

        try {
            $activity = $user->activityLogs()->create($data);
        } catch (QueryException $exception) {
            if ($newAttachmentPath !== null) {
                Storage::disk($attachmentDisk)->delete($newAttachmentPath);
            }

            $existingActivity = ActivityLog::query()
                ->whereBelongsTo($user)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existingActivity !== null) {
                return $this->activityReplayResponse($request, $existingActivity, $data['request_fingerprint']);
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($newAttachmentPath !== null) {
                Storage::disk($attachmentDisk)->delete($newAttachmentPath);
            }

            throw $exception;
        }

        return $this->activityStoredResponse($request, $activity, 'Activity log saved.', 201);
    }

    public function edit(Request $request, ActivityLog $activity): View
    {
        $this->ownedActivity($request, $activity);
        Gate::authorize('update', $activity);

        return view('activities.edit', compact('activity'));
    }

    public function update(
        UpdateActivityLogRequest $request,
        ActivityLog $activity,
        EntryFingerprint $fingerprint,
    ): RedirectResponse {
        $this->ownedActivity($request, $activity);
        Gate::authorize('update', $activity);
        $data = $request->safe()->only([
            'activity_date',
            'tasks_completed',
            'skills_tools',
            'lessons_learned',
            'challenges_notes',
        ]);
        $data['request_fingerprint'] = $fingerprint->activity($data);
        $oldAttachmentPath = $activity->attachment_path;
        $newAttachmentPath = null;
        $attachmentDisk = $this->attachmentDisk();

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $newAttachmentPath = $attachment->store("activity-attachments/{$activity->user_id}", $attachmentDisk);
            $data += [
                'attachment_path' => $newAttachmentPath,
                'attachment_original_name' => $attachment->getClientOriginalName(),
                'attachment_mime_type' => $attachment->getMimeType(),
                'attachment_size' => $attachment->getSize(),
            ];
        } elseif ($request->boolean('remove_attachment')) {
            $data += [
                'attachment_path' => null,
                'attachment_original_name' => null,
                'attachment_mime_type' => null,
                'attachment_size' => null,
            ];
        }

        try {
            $activity->update($data);
        } catch (Throwable $exception) {
            if ($newAttachmentPath !== null) {
                Storage::disk($attachmentDisk)->delete($newAttachmentPath);
            }

            throw $exception;
        }

        if (($newAttachmentPath !== null || $request->boolean('remove_attachment')) && $oldAttachmentPath !== null) {
            Storage::disk($attachmentDisk)->delete($oldAttachmentPath);
        }

        return to_route('activities.index')->with('success', 'Activity log updated.');
    }

    public function destroy(Request $request, ActivityLog $activity): RedirectResponse
    {
        $this->ownedActivity($request, $activity);
        Gate::authorize('delete', $activity);

        if ($activity->attachment_path !== null) {
            Storage::disk($this->attachmentDisk())->delete($activity->attachment_path);
        }

        $activity->delete();

        return to_route('activities.index')->with('success', 'Activity log deleted.');
    }

    public function downloadAttachment(Request $request, ActivityLog $activity): StreamedResponse
    {
        $this->ownedActivity($request, $activity);
        Gate::authorize('view', $activity);
        $attachmentDisk = Storage::disk($this->attachmentDisk());
        abort_if($activity->attachment_path === null || ! $attachmentDisk->exists($activity->attachment_path), 404);

        return $attachmentDisk->download(
            $activity->attachment_path,
            $activity->attachment_original_name,
            ['Content-Type' => $activity->attachment_mime_type ?? 'application/octet-stream'],
        );
    }

    private function ownedActivity(Request $request, ActivityLog $activity): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $activity->user_id === $user->id, 404);

        return $user;
    }

    private function attachmentDisk(): string
    {
        return (string) config('ojt.attachments.disk', 'local');
    }

    private function activityReplayResponse(
        Request $request,
        ActivityLog $activity,
        string $requestFingerprint,
    ): RedirectResponse|JsonResponse {
        if (! hash_equals($activity->request_fingerprint, $requestFingerprint)) {
            $message = 'This submission key was already used for different activity details. Review the form and submit it with a new key.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'conflict',
                    'conflict_type' => 'idempotency_key',
                    'message' => $message,
                ], 409);
            }

            return back()->withErrors(['idempotency_key' => $message])->withInput();
        }

        return $this->activityStoredResponse($request, $activity, 'This activity submission was already saved.', 200);
    }

    private function activityStoredResponse(
        Request $request,
        ActivityLog $activity,
        string $message,
        int $status,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'synced',
                'message' => $message,
                'record' => [
                    'id' => $activity->id,
                    'activity_date' => $activity->activity_date->toDateString(),
                ],
            ], $status);
        }

        return to_route('activities.index')->with('success', $message);
    }
}
