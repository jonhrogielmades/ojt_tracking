<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'student_id' => fake()->unique()->numerify('#######').'-'.fake()->randomElement(['1', '2']),
            'program' => fake()->randomElement(['BSIT', 'BSCS', 'BSECE']),
            'section' => fake()->bothify('#?'),
            'offline_storage_key' => Str::uuid()->toString(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Student,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->superAdmin();
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => null,
            'program' => null,
            'section' => null,
            'offline_storage_key' => null,
            'role' => UserRole::SuperAdmin,
        ]);
    }

    public function ojtCoordinator(): static
    {
        return $this->administrator(UserRole::OjtCoordinator);
    }

    public function auditor(): static
    {
        return $this->administrator(UserRole::Auditor);
    }

    public function documentOfficer(): static
    {
        return $this->administrator(UserRole::DocumentOfficer);
    }

    public function withMfa(): static
    {
        return $this->state(fn (array $attributes) => [
            'mfa_enabled' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function administrator(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => null,
            'program' => null,
            'section' => null,
            'offline_storage_key' => null,
            'role' => $role,
        ]);
    }
}
