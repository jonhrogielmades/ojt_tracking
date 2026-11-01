<?php

namespace App\Http\Controllers;

use App\Actions\SubmitWeeklySubmissionAction;
use App\Http\Requests\StoreWeeklySubmissionRequest;
use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use App\Models\WeeklySubmission;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeeklySubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $submissions = WeeklySubmission::query()
            ->whereBelongsTo($user)
            ->withCount('dtrEntries')
            ->withSum('dtrEntries', 'total_minutes')
            ->latest('week_start')
            ->paginate(12);

        return view('submissions.index', compact('submissions'));
    }

    public function create(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $weekStart = $this->requestedWeek($request);
        $weekEnd = $weekStart->endOfWeek();
        $submission = WeeklySubmission::query()
            ->whereBelongsTo($user)
            ->whereDate('week_start', $weekStart)
            ->with('dtrEntries:id,weekly_submission_id')
            ->first();

        $dtrEntries = DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where(function ($query) use ($submission): void {
                $query->whereNull('weekly_submission_id');

                if ($submission !== null) {
                    $query->orWhere('weekly_submission_id', $submission->id);
                }
            })
            ->orderBy('work_date')
            ->get();
        $activities = ActivityLog::query()
            ->whereBelongsTo($user)
            ->whereBetween('activity_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('activity_date')
            ->get();
        $generatedSummary = $submission?->summary ?: $this->summaryFromActivities($activities);
        $selectedEntryIds = $submission?->dtrEntries->modelKeys() ?? $dtrEntries->modelKeys();

        return view('submissions.create', compact(
            'weekStart',
            'weekEnd',
            'submission',
            'dtrEntries',
            'activities',
            'generatedSummary',
            'selectedEntryIds',
        ));
    }

    public function store(
        StoreWeeklySubmissionRequest $request,
        SubmitWeeklySubmissionAction $submitWeeklySubmission,
    ): RedirectResponse {
        $submission = $submitWeeklySubmission->execute(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        $message = $submission->status->value === 'submitted'
            ? 'Your weekly accomplishment report was submitted for review.'
            : 'Your weekly report draft was saved.';

        return to_route('submissions.show', $submission)->with('success', $message);
    }

    public function show(Request $request, WeeklySubmission $submission): View
    {
        $this->ensureOwned($request, $submission);
        $submission->load([
            'dtrEntries' => fn ($query) => $query->orderBy('work_date'),
            'reviewer:id,name',
        ]);

        return view('submissions.show', compact('submission'));
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function ensureOwned(Request $request, WeeklySubmission $submission): void
    {
        abort_unless($submission->user_id === $this->authenticatedUser($request)->id, 404);
    }

    private function requestedWeek(Request $request): CarbonImmutable
    {
        try {
            $requested = CarbonImmutable::parse($request->string('week')->toString() ?: now()->toDateString());
        } catch (\Throwable) {
            $requested = CarbonImmutable::now();
        }

        return $requested->startOfWeek()->min(CarbonImmutable::now()->startOfWeek());
    }

    private function summaryFromActivities(iterable $activities): string
    {
        $lines = collect($activities)->map(function (ActivityLog $activity): string {
            $line = $activity->activity_date->format('D, M j').': '.trim($activity->tasks_completed);

            if (filled($activity->skills_tools)) {
                $line .= ' Skills/tools: '.trim((string) $activity->skills_tools).'.';
            }

            return $line;
        });

        return $lines->isEmpty()
            ? 'Add activity logs for this week, then edit this summary before saving.'
            : $lines->implode("\n");
    }
}
