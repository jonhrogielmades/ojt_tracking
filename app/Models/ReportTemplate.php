<?php

namespace App\Models;

use Database\Factories\ReportTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'institution_name',
    'office_name',
    'monthly_title',
    'weekly_title',
    'prepared_by_label',
    'approved_by_label',
    'signatory_name',
    'signatory_title',
    'is_active',
    'active_slot',
])]
class ReportTemplate extends Model
{
    /** @use HasFactory<ReportTemplateFactory> */
    use HasFactory;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (ReportTemplate $template): void {
            $template->active_slot = $template->is_active ? 1 : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_slot' => 'integer',
        ];
    }
}
