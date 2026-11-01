<?php

namespace App\Http\Controllers;

use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Http\Requests\StoreCorrectionRequestRequest;
use App\Models\CorrectionRequest;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $correctionRequests = CorrectionRequest::query()
            ->whereBelongsTo($user)
            ->with(['dtrEntry:id,work_date,total_minutes,weekly_submission_id', 'reviewer:id,name'])
            ->latest()
            ->paginate(12);
        $eligibleDtrEntries = DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereHas('weeklySubmission', fn ($query) => $query->where('status', WeeklySubmissionStatus::Approved->value))
            ->whereDoesntHave('correctionRequests', fn ($query) => $query->where('status', CorrectionRequestStatus::Pending->value))
            ->orderByDesc('work_date')
            ->get(['id', 'work_date', 'total_minutes', 'weekly_submission_id']);

        return view('corrections.index', compact('correctionRequests', 'eligibleDtrEntries'));
    }

    public function store(StoreCorrectionRequestRequest $request, DtrEntry $dtr): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $hasPendingRequest = CorrectionRequest::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($dtr)
            ->where('status', CorrectionRequestStatus::Pending->value)
            ->exists();

        if ($hasPendingRequest) {
            return to_route('corrections.index')->withErrors([
                'reason' => 'A correction request for this DTR entry is already pending.',
            ]);
        }

        try {
            CorrectionRequest::query()->create([
                'user_id' => $user->id,
                'dtr_entry_id' => $dtr->id,
                'reason' => trim((string) $request->validated('reason')),
                'status' => CorrectionRequestStatus::Pending,
            ]);
        } catch (QueryException $exception) {
            if (CorrectionRequest::query()->whereBelongsTo($dtr)->where('is_open', true)->exists()) {
                return to_route('corrections.index')->withErrors([
                    'reason' => 'A correction request for this DTR entry is already pending.',
                ]);
            }

            throw $exception;
        }

        return to_route('corrections.index')->with('success', 'Your correction request was sent for review.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
