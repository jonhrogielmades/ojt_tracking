<?php

namespace App\Services;

use App\Enums\WeeklySubmissionStatus;
use App\Models\User;
use App\Models\WeeklySubmission;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

class WeeklySubmissionLockService
{
    public function lockStudent(User $student): void
    {
        User::query()
            ->whereKey($student->getKey())
            ->lockForUpdate()
            ->value('id');
    }

    public function isDateLocked(User $student, DateTimeInterface|string $workDate): bool
    {
        $weekStart = CarbonImmutable::parse($workDate)->startOfWeek()->toDateString();

        return WeeklySubmission::query()
            ->whereBelongsTo($student)
            ->where('week_start', $weekStart)
            ->whereIn('status', [
                WeeklySubmissionStatus::Submitted->value,
                WeeklySubmissionStatus::Approved->value,
            ])
            ->exists();
    }

    /**
     * @throws ValidationException
     */
    public function ensureDateIsUnlocked(User $student, DateTimeInterface|string $workDate): void
    {
        if ($this->isDateLocked($student, $workDate)) {
            throw ValidationException::withMessages([
                'work_date' => 'This week is locked because its weekly submission is submitted or approved.',
            ]);
        }
    }
}
