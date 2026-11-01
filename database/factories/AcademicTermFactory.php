<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->year().' Academic Term',
            'code' => fake()->unique()->bothify('TERM-####-??'),
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->addMonths(4)->endOfMonth()->toDateString(),
            'required_minutes' => 486 * 60,
            'programs' => ['BSIT', 'BSCS'],
            'sections' => ['4A', '4B'],
            'holidays' => [],
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => true]);
    }
}
