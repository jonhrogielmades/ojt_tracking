<?php

namespace App\Services;

use App\Models\DtrEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DtrEntryService
{
    public function __construct(
        private DtrTimeCalculator $calculator,
        private EntryFingerprint $fingerprint,
        private WeeklySubmissionLockService $weeklySubmissionLocks,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): DtrEntry
    {
        return DB::transaction(function () use ($user, $data): DtrEntry {
            $this->weeklySubmissionLocks->lockStudent($user);
            $this->weeklySubmissionLocks->ensureDateIsUnlocked($user, (string) $data['work_date']);

            $values = $this->valuesForPersistence($data);
            $values['idempotency_key'] = ! empty($data['idempotency_key'])
                ? (string) $data['idempotency_key']
                : Str::uuid()->toString();

            return $user->dtrEntries()->create($values);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, DtrEntry $dtr, array $data): DtrEntry
    {
        return DB::transaction(function () use ($user, $dtr, $data): DtrEntry {
            $this->weeklySubmissionLocks->lockStudent($user);
            $lockedDtr = DtrEntry::query()
                ->whereBelongsTo($user)
                ->whereKey($dtr->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedDtr->load('weeklySubmission:id,status,week_start');
            $this->weeklySubmissionLocks->ensureDateIsUnlocked($user, $lockedDtr->work_date);
            $this->weeklySubmissionLocks->ensureDateIsUnlocked($user, (string) $data['work_date']);

            if ($lockedDtr->isLocked()) {
                throw ValidationException::withMessages([
                    'dtr' => 'This DTR entry is locked while its weekly submission is submitted or approved.',
                ]);
            }

            $originalWeek = $lockedDtr->work_date->toImmutable()->startOfWeek();
            $targetWeek = CarbonImmutable::parse((string) $data['work_date'])->startOfWeek();

            if (! $originalWeek->isSameDay($targetWeek)) {
                $lockedDtr->weekly_submission_id = null;
                $lockedDtr->unsetRelation('weeklySubmission');
            }

            $before = $this->historySnapshot($lockedDtr);
            $lockedDtr->fill($this->valuesForPersistence($data));
            $after = $this->historySnapshot($lockedDtr);
            $changes = [];

            foreach ($after as $attribute => $newValue) {
                $oldValue = $before[$attribute];

                if ($oldValue !== $newValue) {
                    $changes[$attribute] = ['old' => $oldValue, 'new' => $newValue];
                }
            }

            $lockedDtr->save();

            if ($changes !== []) {
                $lockedDtr->editHistories()->create([
                    'user_id' => $user->id,
                    'changes' => $changes,
                ]);
            }

            return $lockedDtr->refresh();
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(User $user, DtrEntry $dtr): void
    {
        DB::transaction(function () use ($user, $dtr): void {
            $this->weeklySubmissionLocks->lockStudent($user);
            $lockedDtr = DtrEntry::query()
                ->whereBelongsTo($user)
                ->whereKey($dtr->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->weeklySubmissionLocks->ensureDateIsUnlocked($user, $lockedDtr->work_date);
            $lockedDtr->load('weeklySubmission:id,status');

            if ($lockedDtr->isLocked()) {
                throw ValidationException::withMessages([
                    'dtr' => 'This DTR entry cannot be deleted while its weekly submission is submitted or approved.',
                ]);
            }

            $lockedDtr->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function valuesForPersistence(array $data): array
    {
        $values = [
            'work_date' => (string) $data['work_date'],
            'time_in' => $this->databaseTime((string) $data['time_in']),
            'break_start' => $this->databaseTimeOrNull($data['break_start'] ?? null),
            'break_end' => $this->databaseTimeOrNull($data['break_end'] ?? null),
            'time_out' => $this->databaseTime((string) $data['time_out']),
            'notes' => $this->nullableText($data['notes'] ?? null),
        ];

        $values['total_minutes'] = $this->calculator->calculate($values);
        $values['request_fingerprint'] = $this->fingerprint->dtr($values);

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function historySnapshot(DtrEntry $dtr): array
    {
        $attributes = $dtr->getAttributes();

        return [
            'work_date' => (string) ($attributes['work_date'] ?? ''),
            'time_in' => substr((string) ($attributes['time_in'] ?? ''), 0, 5),
            'break_start' => $this->historyTime($attributes['break_start'] ?? null),
            'break_end' => $this->historyTime($attributes['break_end'] ?? null),
            'time_out' => substr((string) ($attributes['time_out'] ?? ''), 0, 5),
            'total_minutes' => (int) ($attributes['total_minutes'] ?? 0),
            'notes' => $attributes['notes'] ?? null,
        ];
    }

    private function databaseTime(string $value): string
    {
        return substr($value, 0, 5).':00';
    }

    private function databaseTimeOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->databaseTime((string) $value);
    }

    private function historyTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}
