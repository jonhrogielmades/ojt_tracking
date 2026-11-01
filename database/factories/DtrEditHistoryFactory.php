<?php

namespace Database\Factories;

use App\Models\DtrEditHistory;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DtrEditHistory>
 */
class DtrEditHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dtr_entry_id' => DtrEntry::factory(),
            'user_id' => User::factory(),
            'changes' => [
                'notes' => ['old' => null, 'new' => 'Updated note'],
            ],
        ];
    }
}
