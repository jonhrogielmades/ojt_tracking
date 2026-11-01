<?php

namespace App\Models;

use App\Enums\WeeklySubmissionStatus;
use Database\Factories\DtrEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'weekly_submission_id',
    'work_date',
    'time_in',
    'break_start',
    'break_end',
    'time_out',
    'total_minutes',
    'notes',
    'idempotency_key',
    'request_fingerprint',
])]
#[Hidden(['request_fingerprint'])]
class DtrEntry extends Model
{
    /** @use HasFactory<DtrEntryFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weeklySubmission(): BelongsTo
    {
        return $this->belongsTo(WeeklySubmission::class);
    }

    public function editHistories(): HasMany
    {
        return $this->hasMany(DtrEditHistory::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function isLocked(): bool
    {
        return $this->weeklySubmission?->status->locksDtrEntries() ?? false;
    }

    public function canRequestCorrection(): bool
    {
        return $this->weeklySubmission?->status === WeeklySubmissionStatus::Approved;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
            'total_minutes' => 'integer',
        ];
    }
}
