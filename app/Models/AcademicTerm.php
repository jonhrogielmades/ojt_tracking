<?php

namespace App\Models;

use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'code',
    'starts_on',
    'ends_on',
    'required_minutes',
    'programs',
    'sections',
    'holidays',
    'is_active',
    'active_slot',
])]
class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (AcademicTerm $term): void {
            $term->active_slot = $term->is_active ? 1 : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'required_minutes' => 'integer',
            'programs' => 'array',
            'sections' => 'array',
            'holidays' => 'array',
            'is_active' => 'boolean',
            'active_slot' => 'integer',
        ];
    }
}
