<?php

namespace App\Enums;

enum WeeklySubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ChangesRequested => 'Changes requested',
        };
    }

    public function locksDtrEntries(): bool
    {
        return in_array($this, [self::Submitted, self::Approved], true);
    }

    public function canBeEdited(): bool
    {
        return ! $this->locksDtrEntries();
    }
}
