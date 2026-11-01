<?php

namespace App\Models;

use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'partner_company_id',
    'company_name',
    'department',
    'company_address',
    'supervisor_name',
    'supervisor_email',
    'supervisor_phone',
    'ojt_start_date',
    'ojt_end_date',
    'work_days',
    'scheduled_time_in',
    'scheduled_time_out',
    'schedule_notes',
    'required_minutes',
    'weekly_target_minutes',
])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(PartnerCompany::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ojt_start_date' => 'date:Y-m-d',
            'ojt_end_date' => 'date:Y-m-d',
            'work_days' => 'array',
            'required_minutes' => 'integer',
            'weekly_target_minutes' => 'integer',
        ];
    }
}
