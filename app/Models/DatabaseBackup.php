<?php

namespace App\Models;

use Database\Factories\DatabaseBackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['created_by', 'disk', 'path', 'database_driver', 'status', 'size_bytes', 'checksum', 'verified_at', 'failure_reason', 'metadata'])]
class DatabaseBackup extends Model
{
    /** @use HasFactory<DatabaseBackupFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
