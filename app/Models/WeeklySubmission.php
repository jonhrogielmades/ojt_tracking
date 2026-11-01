<?php

namespace App\Models;

use App\Enums\WeeklySubmissionStatus;
use Database\Factories\WeeklySubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'week_start',
    'summary',
    'student_notes',
    'status',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'reviewer_comments',
])]
class WeeklySubmission extends Model
{
    /** @use HasFactory<WeeklySubmissionFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function dtrEntries(): HasMany
    {
        return $this->hasMany(DtrEntry::class);
    }

    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    protected function casts(): array
    {
        return [
            'week_start' => 'date:Y-m-d',
            'status' => WeeklySubmissionStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
