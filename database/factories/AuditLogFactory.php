<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'event' => fake()->randomElement(['auth.login', 'student.created', 'student.updated']),
            'subject_type' => User::class,
            'subject_id' => (string) fake()->numberBetween(1, 1000),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [],
        ];
    }
}
