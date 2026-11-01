<?php

namespace Database\Factories;

use App\Enums\CorrectionRequestStatus;
use App\Models\CorrectionRequest;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionRequest>
 */
class CorrectionRequestFactory extends Factory
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
            'dtr_entry_id' => DtrEntry::factory(),
            'reason' => fake()->paragraph(),
            'status' => CorrectionRequestStatus::Pending,
            'is_open' => true,
            'reviewer_response' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => CorrectionRequestStatus::Approved,
            'is_open' => null,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => CorrectionRequestStatus::Rejected,
            'is_open' => null,
            'reviewed_at' => now(),
        ]);
    }
}
