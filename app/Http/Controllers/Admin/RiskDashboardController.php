<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequirement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RiskDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $requirements = DocumentRequirement::query()
            ->active()
            ->where('is_required', true)
            ->orderBy('due_date')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        $today = CarbonImmutable::today(config('app.timezone'));

        /** @var LengthAwarePaginator<int, User> $students */
        $students = User::query()
            ->students()
            ->where('is_active', true)
            ->with([
                'studentProfile',
                'studentDocuments' => fn ($query) => $query->select('id', 'user_id', 'document_requirement_id', 'review_status'),
            ])
            ->withSum('dtrEntries', 'total_minutes')
            ->withMax('dtrEntries', 'work_date')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25);

        $students->through(function (User $student) use ($requirements, $today): User {
            $profile = $student->studentProfile;
            $completedMinutes = (int) ($student->dtr_entries_sum_total_minutes ?? 0);
            $riskReasons = [];

            if ($profile === null || $profile->ojt_start_date === null || $profile->ojt_end_date === null || $profile->required_minutes <= 0) {
                $riskReasons[] = 'Training plan is incomplete';
            } else {
                $startsOn = CarbonImmutable::parse($profile->ojt_start_date);
                $endsOn = CarbonImmutable::parse($profile->ojt_end_date);
                $totalDays = max(1, $startsOn->diffInDays($endsOn) + 1);
                $elapsedDays = max(0, min($totalDays, $startsOn->diffInDays($today, false) + 1));
                $expectedMinutes = (int) floor($profile->required_minutes * ($elapsedDays / $totalDays));

                if ($today->gte($startsOn) && $completedMinutes + 480 < $expectedMinutes) {
                    $riskReasons[] = 'Logged hours are behind the training plan';
                }

                $lastDtrDate = $student->dtr_entries_max_work_date !== null
                    ? CarbonImmutable::parse($student->dtr_entries_max_work_date)
                    : null;
                if ($today->betweenIncluded($startsOn, $endsOn) && ($lastDtrDate === null || $lastDtrDate->lt($today->subDays(7)))) {
                    $riskReasons[] = 'No DTR entry in the last 7 days';
                }
            }

            $overdueCount = $requirements
                ->filter(fn (DocumentRequirement $requirement): bool => ($requirement->program === null || $requirement->program === $student->program)
                    && $requirement->due_date !== null
                    && $requirement->due_date->lt($today))
                ->reject(fn (DocumentRequirement $requirement): bool => $student->studentDocuments->contains(
                    fn ($document): bool => $document->document_requirement_id === $requirement->id && $document->review_status === 'approved',
                ))
                ->count();

            if ($overdueCount > 0) {
                $riskReasons[] = "{$overdueCount} overdue document requirement(s)";
            }

            $student->setAttribute('completed_minutes', $completedMinutes);
            $student->setAttribute('risk_reasons', $riskReasons);

            return $student;
        });

        return view('admin.risk-dashboard', compact('students'));
    }
}
