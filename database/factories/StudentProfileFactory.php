<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'partner_company_id' => null,
            'company_name' => fake()->company(),
            'department' => 'Information Technology',
            'company_address' => fake()->address(),
            'supervisor_name' => fake()->name(),
            'supervisor_email' => fake()->safeEmail(),
            'supervisor_phone' => fake()->phoneNumber(),
            'ojt_start_date' => now()->startOfMonth()->toDateString(),
            'ojt_end_date' => now()->addMonths(3)->endOfMonth()->toDateString(),
            'work_days' => [1, 2, 3, 4, 5],
            'scheduled_time_in' => '08:00',
            'scheduled_time_out' => '17:00',
            'schedule_notes' => null,
            'required_minutes' => 480 * 60,
            'weekly_target_minutes' => 40 * 60,
        ];
    }
}
