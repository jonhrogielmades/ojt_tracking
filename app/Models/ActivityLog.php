<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'activity_date',
    'tasks_completed',
    'skills_tools',
    'lessons_learned',
    'challenges_notes',
    'attachment_path',
    'attachment_original_name',
    'attachment_mime_type',
    'attachment_size',
    'idempotency_key',
    'request_fingerprint',
])]
#[Hidden(['attachment_path', 'request_fingerprint'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date:Y-m-d',
            'attachment_size' => 'integer',
        ];
    }
}
