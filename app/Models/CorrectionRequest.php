<?php

namespace App\Models;

use App\Enums\CorrectionRequestStatus;
use Database\Factories\CorrectionRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'dtr_entry_id',
    'reason',
    'status',
    'is_open',
    'reviewer_response',
    'reviewed_by',
    'reviewed_at',
])]
class CorrectionRequest extends Model
{
    /** @use HasFactory<CorrectionRequestFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dtrEntry(): BelongsTo
    {
        return $this->belongsTo(DtrEntry::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'status' => CorrectionRequestStatus::class,
            'is_open' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }
}
