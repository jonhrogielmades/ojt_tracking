<?php

namespace Database\Factories;

use App\Enums\WeeklySubmissionStatus;
use App\Models\User;
use App\Models\WeeklySubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklySubmission>
 */
class WeeklySubmissionFactory extends Factory
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
            'week_start' => now()->startOfWeek()->toDateString(),
            'summary' => fake()->paragraph(),
            'student_notes' => fake()->optional()->sentence(),
            'status' => WeeklySubmissionStatus::Draft,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'reviewer_comments' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => WeeklySubmissionStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => WeeklySubmissionStatus::Approved,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
    }
}
