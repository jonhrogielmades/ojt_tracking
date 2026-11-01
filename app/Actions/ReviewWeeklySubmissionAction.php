<?php

namespace App\Actions;

use App\Enums\WeeklySubmissionStatus;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Notifications\WeeklySubmissionReviewedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewWeeklySubmissionAction
{
    public function execute(
        WeeklySubmission $submission,
        User $reviewer,
        WeeklySubmissionStatus $status,
        ?string $comments = null,
    ): WeeklySubmission {
        if (! $reviewer->hasCapability('approvals.manage')) {
            throw new AuthorizationException('You are not allowed to review weekly submissions.');
        }

        if (! in_array($status, [
            WeeklySubmissionStatus::Approved,
            WeeklySubmissionStatus::Rejected,
            WeeklySubmissionStatus::ChangesRequested,
        ], true)) {
            throw ValidationException::withMessages(['status' => 'Choose a valid review decision.']);
        }

        $reviewed = DB::transaction(function () use ($submission, $reviewer, $status, $comments): WeeklySubmission {
            $locked = WeeklySubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status !== WeeklySubmissionStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Only submitted weeks can be reviewed.']);
            }

            $locked->update([
                'status' => $status,
                'reviewer_comments' => filled($comments) ? trim((string) $comments) : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $locked->load('user');
        });

        $reviewed->user->notify(new WeeklySubmissionReviewedNotification($reviewed));

        return $reviewed;
    }
}
