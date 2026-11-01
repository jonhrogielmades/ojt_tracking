<?php

namespace App\Support;

use App\Models\StudentProfile;
use Carbon\CarbonImmutable;

class StudentWorkDateRules
{
    /** @return list<string> */
    public static function for(int $userId): array
    {
        $profile = StudentProfile::query()
            ->where('user_id', $userId)
            ->first(['ojt_start_date', 'ojt_end_date']);
        $latestDate = CarbonImmutable::today();

        if ($profile?->ojt_end_date !== null && $profile->ojt_end_date->lt($latestDate)) {
            $latestDate = $profile->ojt_end_date->toImmutable();
        }

        $rules = ['before_or_equal:'.$latestDate->toDateString()];

        if ($profile?->ojt_start_date !== null) {
            $rules[] = 'after_or_equal:'.$profile->ojt_start_date->toDateString();
        }

        return $rules;
    }
}
