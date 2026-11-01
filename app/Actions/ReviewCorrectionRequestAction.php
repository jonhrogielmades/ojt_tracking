<?php

namespace App\Actions;

use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Notifications\CorrectionRequestReviewedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewCorrectionRequestAction
{
    public function execute(
        CorrectionRequest $correctionRequest,
        User $reviewer,
        CorrectionRequestStatus $status,
        ?string $response = null,
    ): CorrectionRequest {
        if (! $reviewer->hasCapability('approvals.manage')) {
            throw new AuthorizationException('You are not allowed to review correction requests.');
        }

        if (! in_array($status, [CorrectionRequestStatus::Approved, CorrectionRequestStatus::Rejected], true)) {
            throw ValidationException::withMessages(['status' => 'Choose approve or reject.']);
        }

        $reviewed = DB::transaction(function () use ($correctionRequest, $reviewer, $status, $response): CorrectionRequest {
            $locked = CorrectionRequest::query()->lockForUpdate()->findOrFail($correctionRequest->id);

            if ($locked->status !== CorrectionRequestStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'This correction request has already been reviewed.']);
            }

            $locked->update([
                'status' => $status,
                'is_open' => null,
                'reviewer_response' => filled($response) ? trim((string) $response) : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
            $locked->load(['user', 'dtrEntry.weeklySubmission']);

            if ($status === CorrectionRequestStatus::Approved && $locked->dtrEntry->weeklySubmission !== null) {
                $locked->dtrEntry->weeklySubmission->update([
                    'status' => WeeklySubmissionStatus::ChangesRequested,
                    'reviewer_comments' => filled($response)
                        ? trim((string) $response)
                        : 'Correction request approved. Update the DTR entry and resubmit the week.',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);
            }

            return $locked;
        });

        $reviewed->user->notify(new CorrectionRequestReviewedNotification($reviewed));

        return $reviewed;
    }
}
