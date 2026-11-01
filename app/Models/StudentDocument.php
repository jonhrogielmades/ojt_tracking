<?php

namespace App\Models;

use Database\Factories\StudentDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'document_requirement_id',
    'title',
    'category',
    'storage_path',
    'original_name',
    'mime_type',
    'size',
    'review_status',
    'reviewer_id',
    'review_notes',
    'reviewed_at',
])]
#[Hidden(['storage_path'])]
class StudentDocument extends Model
{
    /** @use HasFactory<StudentDocumentFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
