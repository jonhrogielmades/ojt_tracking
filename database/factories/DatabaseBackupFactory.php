<?php

namespace Database\Factories;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days');

        return [
            'created_by' => User::factory()->admin(),
            'disk' => 'local',
            'path' => 'backups/database-'.fake()->unique()->uuid().'.sqlite',
            'database_driver' => 'sqlite',
            'status' => 'completed',
            'size_bytes' => fake()->numberBetween(1024, 2_000_000),
            'checksum' => fake()->sha256(),
            'verified_at' => $createdAt,
        ];
    }
}
