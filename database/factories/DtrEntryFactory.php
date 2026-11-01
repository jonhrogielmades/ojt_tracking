<?php

namespace Database\Factories;

use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DtrEntry>
 */
class DtrEntryFactory extends Factory
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
            'work_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'time_in' => '08:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'time_out' => '17:00',
            'total_minutes' => 480,
            'notes' => fake()->optional()->sentence(),
            'idempotency_key' => fake()->uuid(),
            'request_fingerprint' => hash('sha256', fake()->uuid()),
        ];
    }
}
