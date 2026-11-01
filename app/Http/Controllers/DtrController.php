<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDtrRequest;
use App\Http\Requests\UpdateDtrRequest;
use App\Models\DtrEntry;
use App\Models\User;
use App\Services\DtrEntryService;
use App\Services\WeeklySubmissionLockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DtrEntry::class);

        /** @var User $user */
        $user = $request->user();
        $entries = DtrEntry::query()
            ->whereBelongsTo($user)
            ->with('weeklySubmission:id,status')
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(15);
        $weeklyMinutes = (int) DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->sum('total_minutes');
        $monthlyMinutes = (int) DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total_minutes');

        return view('dtr.index', compact('entries', 'weeklyMinutes', 'monthlyMinutes'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', DtrEntry::class);

        return view('dtr.create', ['defaultDate' => now()->toDateString()]);
    }

    public function store(StoreDtrRequest $request, DtrEntryService $dtrEntries): RedirectResponse
    {
        Gate::authorize('create', DtrEntry::class);

        /** @var User $user */
        $user = $request->user();

        try {
            $dtrEntries->create($user, $request->safe()->only([
                'work_date',
                'time_in',
                'break_start',
                'break_end',
                'time_out',
                'notes',
                'idempotency_key',
            ]));
        } catch (QueryException $exception) {
            if (DtrEntry::query()->whereBelongsTo($user)->where('work_date', $request->string('work_date'))->exists()) {
                return back()->withErrors(['work_date' => 'You already have a DTR entry for this work date.'])->withInput();
            }

            throw $exception;
        }

        return to_route('dtr.index')->with('success', 'DTR entry saved. Official hours were calculated by the server.');
    }

    public function edit(
        Request $request,
        DtrEntry $dtr,
        WeeklySubmissionLockService $weeklySubmissionLocks,
    ): View|RedirectResponse {
        $user = $this->ownedDtr($request, $dtr);
        Gate::authorize('update', $dtr);

        if ($this->isLocked($dtr) || $weeklySubmissionLocks->isDateLocked($user, $dtr->work_date)) {
            return to_route('dtr.index')->withErrors([
                'dtr' => 'This DTR entry is locked while its weekly submission is submitted or approved.',
            ]);
        }

        return view('dtr.edit', compact('dtr'));
    }

    public function update(UpdateDtrRequest $request, DtrEntry $dtr, DtrEntryService $dtrEntries): RedirectResponse
    {
        $user = $this->ownedDtr($request, $dtr);
        Gate::authorize('update', $dtr);

        if ($this->isLocked($dtr)) {
            return to_route('dtr.index')->withErrors([
                'dtr' => 'This DTR entry is locked. Request a correction if an approved entry needs to change.',
            ]);
        }

        try {
            $dtrEntries->update($user, $dtr, $request->safe()->only([
                'work_date',
                'time_in',
                'break_start',
                'break_end',
                'time_out',
                'notes',
            ]));
        } catch (QueryException $exception) {
            if (DtrEntry::query()
                ->whereBelongsTo($user)
                ->where('work_date', $request->string('work_date'))
                ->whereKeyNot($dtr->getKey())
                ->exists()) {
                return back()->withErrors(['work_date' => 'You already have a DTR entry for this work date.'])->withInput();
            }

            throw $exception;
        }

        return to_route('dtr.index')->with('success', 'DTR entry updated and the change history was recorded.');
    }

    public function destroy(
        Request $request,
        DtrEntry $dtr,
        DtrEntryService $dtrEntries,
        WeeklySubmissionLockService $weeklySubmissionLocks,
    ): RedirectResponse {
        $user = $this->ownedDtr($request, $dtr);
        Gate::authorize('delete', $dtr);

        if ($this->isLocked($dtr) || $weeklySubmissionLocks->isDateLocked($user, $dtr->work_date)) {
            return to_route('dtr.index')->withErrors([
                'dtr' => 'This DTR entry cannot be deleted while its weekly submission is submitted or approved.',
            ]);
        }

        try {
            $dtrEntries->delete($user, $dtr);
        } catch (ValidationException $exception) {
            return to_route('dtr.index')->withErrors($exception->errors());
        }

        return to_route('dtr.index')->with('success', 'DTR entry deleted.');
    }

    public function history(Request $request, DtrEntry $dtr): View
    {
        $user = $this->ownedDtr($request, $dtr);
        Gate::authorize('view', $dtr);
        $histories = $dtr->editHistories()
            ->whereBelongsTo($user)
            ->latest()
            ->get();

        return view('dtr.history', compact('dtr', 'histories'));
    }

    private function ownedDtr(Request $request, DtrEntry $dtr): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $dtr->user_id === $user->id, 404);

        return $user;
    }

    private function isLocked(DtrEntry $dtr): bool
    {
        return $dtr->loadMissing('weeklySubmission:id,status')->isLocked();
    }
}
