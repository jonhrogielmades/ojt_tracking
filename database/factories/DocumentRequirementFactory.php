<?php

namespace Database\Factories;

use App\Models\DocumentRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRequirement>
 */
class DocumentRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'category' => fake()->randomElement(['School requirement', 'Evaluation', 'Certificate']),
            'description' => fake()->sentence(),
            'program' => null,
            'due_date' => now()->addWeeks(2)->toDateString(),
            'is_required' => true,
            'is_active' => true,
        ];
    }
}
