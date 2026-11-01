<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\ReportTemplate;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function monthly(Request $request): View
    {
        return view('reports.monthly', $this->monthlyData($request));
    }

    public function monthlyPrint(Request $request): View
    {
        return view('reports.monthly', [
            ...$this->monthlyData($request),
            'printMode' => true,
        ]);
    }

    public function weekly(Request $request): View
    {
        return view('reports.weekly', $this->weeklyData($request));
    }

    public function weeklyPrint(Request $request): View
    {
        return view('reports.weekly', [
            ...$this->weeklyData($request),
            'printMode' => true,
        ]);
    }

    public function dtrCsv(Request $request): StreamedResponse
    {
        $data = $this->monthlyData($request);
        /** @var User $user */
        $user = $data['user'];
        /** @var StudentProfile|null $profile */
        $profile = $data['profile'];
        /** @var CarbonImmutable $month */
        $month = $data['month'];
        /** @var EloquentCollection<int, DtrEntry> $dtrEntries */
        $dtrEntries = $data['dtrEntries'];
        /** @var ReportTemplate|null $reportTemplate */
        $reportTemplate = $data['reportTemplate'];

        return response()->streamDownload(function () use ($user, $profile, $month, $dtrEntries, $reportTemplate): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, [$this->csvCell($reportTemplate?->institution_name ?? config('app.name'))]);
            fputcsv($stream, [$this->csvCell($reportTemplate?->monthly_title ?? 'OJT Monthly DTR Report')]);
            fputcsv($stream, ['Student', $this->csvCell($user->name)]);
            fputcsv($stream, ['Student ID', $this->csvCell($user->student_id)]);
            fputcsv($stream, ['Program / Section', $this->csvCell($user->program.' / '.$user->section)]);
            fputcsv($stream, ['Company', $this->csvCell($profile?->company_name ?? '')]);
            fputcsv($stream, ['Month', $month->format('F Y')]);
            fputcsv($stream, []);
            fputcsv($stream, ['Work Date', 'Time In', 'Break Start', 'Break End', 'Time Out', 'Official Hours', 'Notes']);

            foreach ($dtrEntries as $entry) {
                fputcsv($stream, [
                    $entry->work_date->format('Y-m-d'),
                    $this->formatTime($entry->time_in),
                    $this->formatTime($entry->break_start),
                    $this->formatTime($entry->break_end),
                    $this->formatTime($entry->time_out),
                    number_format($entry->total_minutes / 60, 2, '.', ''),
                    $this->csvCell($entry->notes ?? ''),
                ]);
            }

            fclose($stream);
        }, 'ojt-dtr-'.$month->format('Y-m').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{
     *     user: User,
     *     profile: StudentProfile|null,
     *     month: CarbonImmutable,
     *     dtrEntries: EloquentCollection<int, DtrEntry>,
     *     monthlyMinutes: int,
     *     completedMinutes: int,
     *     requiredMinutes: int,
     *     remainingMinutes: int,
     *     reportTemplate: ReportTemplate|null
     * }
     */
    private function monthlyData(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $user = $this->authenticatedUser($request);
        $month = isset($validated['month'])
            ? CarbonImmutable::parse($validated['month'].'-01')->startOfMonth()
            : CarbonImmutable::today(config('app.timezone'))->startOfMonth();
        $profile = StudentProfile::query()->whereBelongsTo($user)->first();
        $dtrEntries = DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();
        $monthlyMinutes = (int) $dtrEntries->sum('total_minutes');
        $completedMinutes = (int) DtrEntry::query()->whereBelongsTo($user)->sum('total_minutes');
        $requiredMinutes = $profile?->required_minutes ?? 0;

        return [
            'user' => $user,
            'profile' => $profile,
            'month' => $month,
            'dtrEntries' => $dtrEntries,
            'monthlyMinutes' => $monthlyMinutes,
            'completedMinutes' => $completedMinutes,
            'requiredMinutes' => $requiredMinutes,
            'remainingMinutes' => max(0, $requiredMinutes - $completedMinutes),
            'reportTemplate' => ReportTemplate::query()->active()->orderByDesc('updated_at')->orderByDesc('id')->first(),
        ];
    }

    /**
     * @return array{
     *     user: User,
     *     profile: StudentProfile|null,
     *     weekStart: CarbonImmutable,
     *     weekEnd: CarbonImmutable,
     *     activityLogs: EloquentCollection<int, ActivityLog>,
     *     selectedActivityLogs: EloquentCollection<int, ActivityLog>,
     *     weeklyDtrMinutes: int,
     *     reportTemplate: ReportTemplate|null
     * }
     */
    private function weeklyData(Request $request): array
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date_format:Y-m-d'],
            'activity_ids' => ['nullable', 'array'],
            'activity_ids.*' => ['integer', 'distinct'],
        ]);
        $user = $this->authenticatedUser($request);
        $selectedIds = collect($validated['activity_ids'] ?? [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $selectedActivityLogs = new EloquentCollection;
        if ($selectedIds->isNotEmpty()) {
            $selectedActivityLogs = ActivityLog::query()
                ->whereBelongsTo($user)
                ->whereIn('id', $selectedIds->all())
                ->orderBy('activity_date')
                ->orderBy('id')
                ->get();
            abort_if($selectedActivityLogs->count() !== $selectedIds->count(), 404);
        }

        $selectedDate = isset($validated['week'])
            ? CarbonImmutable::parse($validated['week'])
            : ($selectedActivityLogs->first()?->activity_date !== null
                ? CarbonImmutable::parse($selectedActivityLogs->first()->activity_date)
                : CarbonImmutable::today(config('app.timezone')));
        $weekStart = $selectedDate->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $weekEnd = $selectedDate->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();
        $profile = StudentProfile::query()->whereBelongsTo($user)->first();
        $activityLogs = ActivityLog::query()
            ->whereBelongsTo($user)
            ->whereBetween('activity_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('activity_date')
            ->orderBy('id')
            ->get();

        if ($selectedIds->isEmpty()) {
            $selectedActivityLogs = $activityLogs;
        } elseif ($selectedActivityLogs->contains(function (ActivityLog $activity) use ($weekStart, $weekEnd): bool {
            return $activity->activity_date->lt($weekStart) || $activity->activity_date->gt($weekEnd);
        })) {
            throw ValidationException::withMessages([
                'activity_ids' => 'Select activity logs from one Monday-to-Sunday week only.',
            ]);
        }

        $weeklyDtrMinutes = (int) DtrEntry::query()
            ->whereBelongsTo($user)
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('total_minutes');

        return [
            'user' => $user,
            'profile' => $profile,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'activityLogs' => $activityLogs,
            'selectedActivityLogs' => $selectedActivityLogs,
            'weeklyDtrMinutes' => $weeklyDtrMinutes,
            'reportTemplate' => ReportTemplate::query()->active()->orderByDesc('updated_at')->orderByDesc('id')->first(),
        ];
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function csvCell(mixed $value): string
    {
        $cell = str_replace("\0", '', (string) $value);

        return preg_match('/^\s*[=+\-@]/u', $cell) === 1 ? "'".$cell : $cell;
    }

    private function formatTime(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return $value === null ? '' : mb_substr((string) $value, 0, 5);
    }
}
