<?php

namespace Database\Factories;

use App\Models\PartnerCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerCompany>
 */
class PartnerCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'address' => fake()->address(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'departments' => ['Information Technology'],
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
