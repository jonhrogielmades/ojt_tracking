<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\DatabaseBackup;
use App\Models\StudentDocument;
use App\Models\User;
use App\Models\WeeklySubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminAlertController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $administrator */
        $administrator = $request->user();
        $canManageApprovals = $administrator->hasCapability('approvals.manage');
        $canManageDocuments = $administrator->hasCapability('documents.manage');
        $canViewSystemHealth = $administrator->hasCapability('system-health.view');
        $canManageBackups = $administrator->hasCapability('backups.manage');

        $submittedWeeklyCount = 0;
        $pendingCorrectionCount = 0;
        $submittedWeeklyReports = new Collection;
        $pendingCorrections = new Collection;

        if ($canManageApprovals) {
            $submittedWeeklyCount = WeeklySubmission::query()
                ->where('status', WeeklySubmissionStatus::Submitted)
                ->count();
            $pendingCorrectionCount = CorrectionRequest::query()
                ->where('status', CorrectionRequestStatus::Pending)
                ->count();
            $submittedWeeklyReports = WeeklySubmission::query()
                ->where('status', WeeklySubmissionStatus::Submitted)
                ->with('user:id,name,student_id')
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->limit(5)
                ->get();
            $pendingCorrections = CorrectionRequest::query()
                ->where('status', CorrectionRequestStatus::Pending)
                ->with(['user:id,name,student_id', 'dtrEntry:id,work_date'])
                ->oldest()
                ->orderBy('id')
                ->limit(5)
                ->get();
        }

        $pendingDocumentCount = 0;
        $pendingDocuments = new Collection;

        if ($canManageDocuments) {
            $pendingDocumentCount = StudentDocument::query()
                ->where('review_status', 'pending')
                ->count();
            $pendingDocuments = StudentDocument::query()
                ->where('review_status', 'pending')
                ->with(['user:id,name,student_id', 'requirement:id,name'])
                ->oldest()
                ->orderBy('id')
                ->limit(5)
                ->get();
        }

        $failedJobCount = null;
        if ($canViewSystemHealth && Schema::hasTable('failed_jobs')) {
            $failedJobCount = DB::table('failed_jobs')->count();
        }

        $latestBackup = ($canViewSystemHealth || $canManageBackups) && Schema::hasTable('database_backups')
            ? DatabaseBackup::query()->latest('id')->first()
            : null;

        /** @var LengthAwarePaginator<DatabaseNotification> $notifications */
        $notifications = $administrator->notifications()
            ->latest()
            ->paginate(10, ['*'], 'notifications_page');

        return view('admin.alerts.index', compact(
            'canManageApprovals',
            'canManageDocuments',
            'canViewSystemHealth',
            'canManageBackups',
            'submittedWeeklyCount',
            'pendingCorrectionCount',
            'submittedWeeklyReports',
            'pendingCorrections',
            'pendingDocumentCount',
            'pendingDocuments',
            'failedJobCount',
            'latestBackup',
            'notifications',
        ));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        /** @var User $administrator */
        $administrator = $request->user();
        $ownedNotification = $administrator->notifications()->findOrFail($notification);
        $ownedNotification->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $administrator */
        $administrator = $request->user();
        $administrator->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All administrator notifications marked as read.');
    }
}
