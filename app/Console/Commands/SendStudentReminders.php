<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\StudentReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

#[Signature('students:send-reminders')]
#[Description('Notify students about missing DTRs, low weekly progress, and approaching document deadlines')]
class SendStudentReminders extends Command
{
    public function handle(): int
    {
        $weekStart = CarbonImmutable::now()->startOfWeek();
        $lookbackStart = CarbonImmutable::now()->subDays(8)->startOfDay();
        $requirements = $this->upcomingRequirements();
        $holidayDates = $this->configuredHolidayDates();
        $relations = [
            'studentProfile',
            'dtrEntries' => fn ($query) => $query
                ->whereBetween('work_date', [$lookbackStart->toDateString(), now()->toDateString()]),
            'notifications' => fn ($query) => $query->whereDate('created_at', today()),
        ];

        if ($requirements->isNotEmpty() && Schema::hasColumn('student_documents', 'document_requirement_id')) {
            $documentColumns = ['id', 'user_id', 'document_requirement_id'];

            if (Schema::hasColumn('student_documents', 'review_status')) {
                $documentColumns[] = 'review_status';
            }

            $relations['studentDocuments'] = fn ($query) => $query->select($documentColumns);
        }

        $sent = 0;
        User::query()
            ->students()
            ->where('is_active', true)
            ->with($relations)
            ->chunkById(100, function (Collection $students) use ($weekStart, $requirements, $holidayDates, &$sent): void {
                foreach ($students as $student) {
                    $sent += $this->sendAttendanceReminder($student, $holidayDates);
                    $sent += $this->sendProgressReminder($student, $weekStart);
                    $sent += $this->sendDocumentReminders($student, $requirements);
                }
            });

        $this->info("Sent {$sent} student reminder(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, string>  $holidayDates
     */
    private function sendAttendanceReminder(User $student, Collection $holidayDates): int
    {
        $workDays = collect($student->studentProfile?->work_days ?: [1, 2, 3, 4, 5])->map(fn (mixed $day): int => (int) $day);

        if ($workDays->isEmpty()) {
            return 0;
        }

        $expectedDate = CarbonImmutable::now()->subDay();

        for ($attempt = 0; $attempt < 7 && ! $workDays->contains($expectedDate->dayOfWeekIso); $attempt++) {
            $expectedDate = $expectedDate->subDay();
        }

        $profile = $student->studentProfile;
        $outsidePlacement = ($profile?->ojt_start_date && $expectedDate->lt($profile->ojt_start_date))
            || ($profile?->ojt_end_date && $expectedDate->gt($profile->ojt_end_date));
        $hasDtr = $student->dtrEntries->contains(
            fn ($entry): bool => $entry->work_date->isSameDay($expectedDate),
        );

        if ($outsidePlacement
            || $holidayDates->contains($expectedDate->toDateString())
            || $hasDtr
            || $this->alreadyReminded($student, 'missing_dtr')) {
            return 0;
        }

        $student->notify(new StudentReminderNotification(
            'missing_dtr',
            'DTR entry may be missing',
            'No DTR was found for '.$expectedDate->format('M j, Y').'. Add it or review your work schedule.',
            '/dtr/create',
        ));

        return 1;
    }

    private function sendProgressReminder(User $student, CarbonImmutable $weekStart): int
    {
        $targetMinutes = (int) ($student->studentProfile?->weekly_target_minutes ?: 2400);
        $workDays = collect($student->studentProfile?->work_days ?: [1, 2, 3, 4, 5])->map(fn (mixed $day): int => (int) $day);
        $elapsedDays = $workDays->filter(fn (int $day): bool => $day <= now()->dayOfWeekIso)->count();
        $expectedMinutes = $workDays->isEmpty() ? 0 : (int) round($targetMinutes * $elapsedDays / $workDays->count());
        $actualMinutes = (int) $student->dtrEntries
            ->filter(fn ($entry): bool => $entry->work_date->gte($weekStart))
            ->sum('total_minutes');

        if ($expectedMinutes === 0 || $actualMinutes >= $expectedMinutes * 0.75 || $this->alreadyReminded($student, 'low_progress')) {
            return 0;
        }

        $student->notify(new StudentReminderNotification(
            'low_progress',
            'Weekly progress is below target',
            'You have logged '.number_format($actualMinutes / 60, 1).' of your '.number_format($targetMinutes / 60, 1).'-hour weekly target.',
            '/progress',
        ));

        return 1;
    }

    /**
     * @param  Collection<int, Model>  $requirements
     */
    private function sendDocumentReminders(User $student, Collection $requirements): int
    {
        if (! $student->relationLoaded('studentDocuments')) {
            return 0;
        }

        $sent = 0;

        foreach ($requirements as $requirement) {
            if (filled($requirement->getAttribute('program')) && $requirement->getAttribute('program') !== $student->program) {
                continue;
            }

            $reminderType = 'document_deadline:'.$requirement->getKey();
            $hasDocument = $student->studentDocuments->contains(
                fn ($document): bool => $document->document_requirement_id === $requirement->getKey()
                    && (! isset($document->review_status) || $document->review_status !== 'rejected'),
            );

            if ($hasDocument || $this->alreadyReminded($student, $reminderType)) {
                continue;
            }

            $dueDate = CarbonImmutable::parse($requirement->getAttribute('due_date'));
            $student->notify(new StudentReminderNotification(
                $reminderType,
                'Document deadline approaching',
                $requirement->getAttribute('name').' is due '.$dueDate->format('M j, Y').'.',
                '/documents',
            ));
            $sent++;
        }

        return $sent;
    }

    private function alreadyReminded(User $student, string $reminderType): bool
    {
        return $student->notifications->contains(
            fn ($notification): bool => ($notification->data['reminder_type'] ?? null) === $reminderType,
        );
    }

    /**
     * @return Collection<int, Model>
     */
    private function upcomingRequirements(): Collection
    {
        $modelClass = 'App\\Models\\DocumentRequirement';

        if (! class_exists($modelClass)) {
            return collect();
        }

        /** @var Model $model */
        $model = new $modelClass;

        if (! Schema::hasTable($model->getTable()) || ! Schema::hasColumn($model->getTable(), 'due_date')) {
            return collect();
        }

        $query = $modelClass::query()
            ->whereBetween('due_date', [today()->toDateString(), today()->addDays(7)->toDateString()])
            ->orderBy('due_date')
            ->orderBy('name')
            ->orderBy('id');

        if (method_exists($modelClass, 'scopeActive')) {
            $query->active();
        } elseif (Schema::hasColumn($model->getTable(), 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn($model->getTable(), 'is_required')) {
            $query->where('is_required', true);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, string>
     */
    private function configuredHolidayDates(): Collection
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

            $rawHolidays = $configuration->getAttribute('holidays')
                ?? $configuration->getAttribute('holiday_dates')
                ?? [];

            if (is_string($rawHolidays)) {
                $rawHolidays = json_decode($rawHolidays, true) ?: [];
            }

            return collect(is_array($rawHolidays) ? $rawHolidays : [])
                ->map(fn (mixed $holiday): ?string => is_array($holiday)
                    ? ($holiday['date'] ?? null)
                    : (is_string($holiday) ? $holiday : null))
                ->filter()
                ->map(function (string $date): ?string {
                    try {
                        return CarbonImmutable::parse($date)->toDateString();
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->values();
        }

        return collect();
    }
}
