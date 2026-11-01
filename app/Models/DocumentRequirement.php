<?php

namespace App\Models;

use Database\Factories\DocumentRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'category',
    'description',
    'program',
    'due_date',
    'is_required',
    'is_active',
])]
class DocumentRequirement extends Model
{
    /** @use HasFactory<DocumentRequirementFactory> */
    use HasFactory;

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForProgram(Builder $query, ?string $program): Builder
    {
        return $query->where(function (Builder $query) use ($program): void {
            $query->whereNull('program');

            if ($program !== null && $program !== '') {
                $query->orWhere('program', $program);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
