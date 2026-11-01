<?php

namespace App\Models;

use Database\Factories\DtrEditHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dtr_entry_id', 'user_id', 'changes'])]
class DtrEditHistory extends Model
{
    /** @use HasFactory<DtrEditHistoryFactory> */
    use HasFactory;

    public function dtrEntry(): BelongsTo
    {
        return $this->belongsTo(DtrEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['changes' => 'array'];
    }
}
