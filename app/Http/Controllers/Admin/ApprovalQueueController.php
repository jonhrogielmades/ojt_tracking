<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReviewCorrectionRequestAction;
use App\Actions\ReviewWeeklySubmissionAction;
use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewCorrectionRequestRequest;
use App\Http\Requests\Admin\ReviewWeeklySubmissionRequest;
use App\Models\CorrectionRequest;
use App\Models\StudentDocument;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApprovalQueueController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $pendingDocuments = StudentDocument::query()
            ->where('review_status', 'pending')
            ->with(['user:id,name,student_id,program,section', 'requirement:id,name'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(50)
            ->get();
        $weeklySubmissions = WeeklySubmission::query()
            ->where('status', WeeklySubmissionStatus::Submitted->value)
            ->with(['user:id,name,student_id,program,section'])
            ->withCount('dtrEntries')
            ->withSum('dtrEntries', 'total_minutes')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->limit(50)
            ->get();
        $correctionRequests = CorrectionRequest::query()
            ->where('status', CorrectionRequestStatus::Pending->value)
            ->with([
                'user:id,name,student_id,program,section',
                'dtrEntry:id,user_id,weekly_submission_id,work_date,total_minutes,notes',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(50)
            ->get();

        return view('admin.approval-queue', compact(
            'pendingDocuments',
            'weeklySubmissions',
            'correctionRequests',
        ));
    }

    public function reviewSubmission(
        ReviewWeeklySubmissionRequest $request,
        WeeklySubmission $submission,
        ReviewWeeklySubmissionAction $reviewSubmission,
    ): RedirectResponse {
        /** @var User $reviewer */
        $reviewer = $request->user();
        $oldValues = $submission->only(['status', 'reviewer_comments', 'reviewed_by', 'reviewed_at']);
        $reviewed = $reviewSubmission->execute(
            $submission,
            $reviewer,
            WeeklySubmissionStatus::from($request->validated('decision')),
            $request->validated('reviewer_comments'),
        );
        $this->auditLogger->record(
            'weekly-submission.reviewed',
            $reviewer,
            $reviewed,
            $oldValues,
            $reviewed->only(['status', 'reviewer_comments', 'reviewed_by', 'reviewed_at']),
        );

        return to_route('admin.approval_queue')->with('status', 'Weekly submission review saved.');
    }

    public function reviewCorrection(
        ReviewCorrectionRequestRequest $request,
        CorrectionRequest $correctionRequest,
        ReviewCorrectionRequestAction $reviewCorrection,
    ): RedirectResponse {
        /** @var User $reviewer */
        $reviewer = $request->user();
        $oldValues = $correctionRequest->only(['status', 'reviewer_response', 'reviewed_by', 'reviewed_at']);
        $reviewed = $reviewCorrection->execute(
            $correctionRequest,
            $reviewer,
            CorrectionRequestStatus::from($request->validated('decision')),
            $request->validated('reviewer_response'),
        );
        $this->auditLogger->record(
            'correction-request.reviewed',
            $reviewer,
            $reviewed,
            $oldValues,
            $reviewed->only(['status', 'reviewer_response', 'reviewed_by', 'reviewed_at']),
        );

        return to_route('admin.approval_queue')->with('status', 'Correction request review saved.');
    }
}
