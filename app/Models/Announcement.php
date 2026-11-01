<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'creator_id',
    'title',
    'body',
    'audience',
    'audience_value',
    'published_at',
    'sent_at',
    'expires_at',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
