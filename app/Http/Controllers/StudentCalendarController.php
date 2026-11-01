<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentCalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $month = CarbonImmutable::createFromFormat('!Y-m', $request->string('month')->toString() ?: now()->format('Y-m'));
        } catch (\Throwable) {
            $month = CarbonImmutable::now()->startOfMonth();
        }

        $month = $month === false ? CarbonImmutable::now()->startOfMonth() : $month->startOfMonth();
        $calendarStart = $month->startOfWeek();
        $calendarEnd = $month->endOfMonth()->endOfWeek();
        $dtrByDate = DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->groupBy(fn (DtrEntry $entry): string => $entry->work_date->toDateString());
        $activitiesByDate = ActivityLog::query()
            ->whereBelongsTo($user)
            ->whereBetween('activity_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('activity_date')
            ->get()
            ->groupBy(fn (ActivityLog $activity): string => $activity->activity_date->toDateString());
        $holidays = collect($this->configuredHolidays())->keyBy('date');
        $days = collect();

        for ($date = $calendarStart; $date->lte($calendarEnd); $date = $date->addDay()) {
            $dateKey = $date->toDateString();
            $days->push([
                'date' => $date,
                'in_month' => $date->month === $month->month,
                'dtr' => $dtrByDate->get($dateKey, collect()),
                'activities' => $activitiesByDate->get($dateKey, collect()),
                'holiday' => $holidays->get($dateKey),
            ]);
        }

        return view('student.calendar', [
            'month' => $month,
            'days' => $days,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * @return array<int, array{date: string, name: string}>
     */
    private function configuredHolidays(): array
    {
        foreach (['App\\Models\\AcademicTerm', 'App\\Models\\AcademicConfiguration'] as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            /** @var Model $model */
            $model = new $modelClass;

            if (! Schema::hasTable($model->getTable())) {
                continue;
            }

            $query = $modelClass::query();

            if (method_exists($modelClass, 'scopeActive')) {
                $query->active();
            } elseif (Schema::hasColumn($model->getTable(), 'is_active')) {
                $query->where('is_active', true);
            }

            $configuration = $query->first();

            if ($configuration === null) {
                continue;
            }

            $rawHolidays = $configuration?->getAttribute('holidays')
                ?? $configuration?->getAttribute('holiday_dates')
                ?? [];

            if (is_string($rawHolidays)) {
                $rawHolidays = json_decode($rawHolidays, true) ?: [];
            }

            return collect(is_array($rawHolidays) ? $rawHolidays : [])
                ->map(function (mixed $holiday): ?array {
                    if (is_string($holiday)) {
                        return ['date' => $holiday, 'name' => 'Holiday'];
                    }

                    if (! is_array($holiday) || blank($holiday['date'] ?? null)) {
                        return null;
                    }

                    return [
                        'date' => (string) $holiday['date'],
                        'name' => Str::limit((string) ($holiday['name'] ?? 'Holiday'), 100),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return [];
    }
}
