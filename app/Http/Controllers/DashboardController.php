<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $profile = StudentProfile::query()->whereBelongsTo($user)->first();
        $requiredMinutes = $profile?->required_minutes ?? 0;
        $completedMinutes = (int) DtrEntry::query()->whereBelongsTo($user)->sum('total_minutes');
        $remainingMinutes = max(0, $requiredMinutes - $completedMinutes);
        $completionPercent = $requiredMinutes > 0
            ? min(100, round(($completedMinutes / $requiredMinutes) * 100, 1))
            : 0;

        $recentDtrEntries = DtrEntry::query()
            ->whereBelongsTo($user)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentActivityLogs = ActivityLog::query()
            ->whereBelongsTo($user)
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'profile' => $profile,
            'requiredMinutes' => $requiredMinutes,
            'completedMinutes' => $completedMinutes,
            'remainingMinutes' => $remainingMinutes,
            'completionPercent' => $completionPercent,
            'recentDtrEntries' => $recentDtrEntries,
            'recentActivityLogs' => $recentActivityLogs,
            'missingDates' => $this->missingWorkDates($user, $profile),
            'incompleteDates' => $this->datesWithoutActivityLogs($user),
        ]);
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function missingWorkDates(User $user, ?StudentProfile $profile): Collection
    {
        if ($profile?->ojt_start_date === null) {
            return collect();
        }

        $today = CarbonImmutable::today(config('app.timezone'));
        $scheduleStart = CarbonImmutable::parse($profile->ojt_start_date)->startOfDay();
        $scheduleEnd = $profile->ojt_end_date === null
            ? $today
            : CarbonImmutable::parse($profile->ojt_end_date)->min($today)->startOfDay();

        if ($scheduleStart->isAfter($scheduleEnd)) {
            return collect();
        }

        $workDays = collect($profile->work_days ?: [1, 2, 3, 4, 5])
            ->map(static fn (mixed $day): int => (int) $day)
            ->all();
        $recordedDates = DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [$scheduleStart->toDateString(), $scheduleEnd->toDateString()])
            ->pluck('work_date')
            ->map(static fn (mixed $date): string => CarbonImmutable::parse((string) $date)->toDateString())
            ->flip();

        $missingDates = collect();
        for (
            $date = $scheduleEnd;
            $date->gte($scheduleStart) && $missingDates->count() < 10;
            $date = $date->subDay()
        ) {
            if (in_array($date->dayOfWeekIso, $workDays, true) && ! $recordedDates->has($date->toDateString())) {
                $missingDates->push($date);
            }
        }

        return $missingDates->values();
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function datesWithoutActivityLogs(User $user): Collection
    {
        $recentDtrDates = DtrEntry::query()
            ->whereBelongsTo($user)
            ->orderByDesc('work_date')
            ->limit(20)
            ->pluck('work_date')
            ->map(static fn (mixed $date): string => CarbonImmutable::parse((string) $date)->toDateString())
            ->unique()
            ->values();

        if ($recentDtrDates->isEmpty()) {
            return collect();
        }

        $activityDates = ActivityLog::query()
            ->whereBelongsTo($user)
            ->whereIn('activity_date', $recentDtrDates->all())
            ->pluck('activity_date')
            ->map(static fn (mixed $date): string => CarbonImmutable::parse((string) $date)->toDateString())
            ->flip();

        return $recentDtrDates
            ->reject(static fn (string $date): bool => $activityDates->has($date))
            ->map(static fn (string $date): CarbonImmutable => CarbonImmutable::parse($date))
            ->take(10)
            ->values();
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
