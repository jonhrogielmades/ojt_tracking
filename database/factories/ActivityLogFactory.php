<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'activity_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'tasks_completed' => fake()->paragraph(),
            'skills_tools' => fake()->words(3, true),
            'lessons_learned' => fake()->sentence(),
            'challenges_notes' => fake()->optional()->sentence(),
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime_type' => null,
            'attachment_size' => null,
            'idempotency_key' => fake()->uuid(),
            'request_fingerprint' => hash('sha256', fake()->uuid()),
        ];
    }
}
