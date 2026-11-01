<?php

namespace App\Models;

use Database\Factories\PartnerCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'address',
    'contact_name',
    'contact_email',
    'contact_phone',
    'departments',
    'notes',
    'is_active',
])]
class PartnerCompany extends Model
{
    /** @use HasFactory<PartnerCompanyFactory> */
    use HasFactory;

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departments' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
