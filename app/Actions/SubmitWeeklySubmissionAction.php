<?php

namespace App\Actions;

use App\Enums\WeeklySubmissionStatus;
use App\Models\DtrEntry;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Services\WeeklySubmissionLockService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitWeeklySubmissionAction
{
    public function __construct(private WeeklySubmissionLockService $weeklySubmissionLocks) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): WeeklySubmission
    {
        return DB::transaction(function () use ($user, $data): WeeklySubmission {
            $weekStart = CarbonImmutable::parse((string) $data['week_start'])->startOfDay();
            $weekEnd = $weekStart->endOfWeek();
            $this->weeklySubmissionLocks->lockStudent($user);
            $submission = WeeklySubmission::query()
                ->whereBelongsTo($user)
                ->whereDate('week_start', $weekStart)
                ->lockForUpdate()
                ->first();

            if ($submission !== null && ! $submission->canBeEdited()) {
                throw ValidationException::withMessages([
                    'week_start' => 'This week is locked while it is submitted or approved.',
                ]);
            }

            $status = $data['intent'] === 'submit'
                ? WeeklySubmissionStatus::Submitted
                : WeeklySubmissionStatus::Draft;
            $entryIds = collect($data['dtr_entry_ids'] ?? [])
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();
            $weekEntryIds = DtrEntry::query()
                ->whereBelongsTo($user)
                ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->lockForUpdate()
                ->pluck('id');

            if ($weekEntryIds->intersect($entryIds)->count() !== $entryIds->count()) {
                throw ValidationException::withMessages([
                    'dtr_entry_ids' => 'Every selected DTR entry must belong to this week.',
                ]);
            }

            if ($status === WeeklySubmissionStatus::Submitted && $weekEntryIds->diff($entryIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'dtr_entry_ids' => 'Include every DTR entry from this week before submitting it for review.',
                ]);
            }

            $submission ??= new WeeklySubmission([
                'user_id' => $user->id,
                'week_start' => $weekStart->toDateString(),
            ]);
            $submission->fill([
                'summary' => trim((string) $data['summary']),
                'student_notes' => filled($data['student_notes'] ?? null)
                    ? trim((string) $data['student_notes'])
                    : null,
                'status' => $status,
                'submitted_at' => $status === WeeklySubmissionStatus::Submitted ? now() : null,
            ]);
            $submission->save();

            $submission->dtrEntries()->whereNotIn('id', $entryIds)->update(['weekly_submission_id' => null]);

            if ($entryIds->isNotEmpty()) {
                DtrEntry::query()
                    ->whereBelongsTo($user)
                    ->whereKey($entryIds)
                    ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->update(['weekly_submission_id' => $submission->id]);
            }

            return $submission->load('dtrEntries');
        });
    }
}
