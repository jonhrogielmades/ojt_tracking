<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory()->admin(),
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'audience' => 'all',
            'audience_value' => null,
            'published_at' => null,
            'sent_at' => null,
            'expires_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => now(),
            'sent_at' => now(),
        ]);
    }
}
