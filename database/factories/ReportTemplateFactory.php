<?php

namespace Database\Factories;

use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'institution_name' => fake()->company(),
            'office_name' => 'Office of Internship and Placement',
            'monthly_title' => 'OJT Monthly DTR Report',
            'weekly_title' => 'OJT Weekly Activity Report',
            'prepared_by_label' => 'Prepared by',
            'approved_by_label' => 'Approved by',
            'signatory_name' => fake()->name(),
            'signatory_title' => 'OJT Coordinator',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => true]);
    }
}
