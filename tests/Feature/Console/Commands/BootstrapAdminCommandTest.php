<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapAdminCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_is_a_successful_no_op_when_initial_configuration_is_not_set(): void
    {
        config()->set('ojt.bootstrap_admin', [
            'name' => null,
            'email' => null,
            'password' => null,
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('nothing to bootstrap')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_creates_the_first_super_administrator_from_configuration(): void
    {
        config()->set('ojt.bootstrap_admin', [
            'name' => 'Initial Administrator',
            'email' => 'INITIAL.ADMIN@EXAMPLE.COM',
            'password' => 'Strong-Initial-Password-2026!',
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('initial.admin@example.com')
            ->assertSuccessful();

        $administrator = User::query()->sole();
        $this->assertSame('Initial Administrator', $administrator->name);
        $this->assertSame('initial.admin@example.com', $administrator->email);
        $this->assertSame(UserRole::SuperAdmin, $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check('Strong-Initial-Password-2026!', $administrator->password));
    }

    public function test_it_skips_creation_without_failing_startup_when_email_is_invalid(): void
    {
        config()->set('ojt.bootstrap_admin', [
            'name' => 'Initial Administrator',
            'email' => 'not-an-email',
            'password' => 'Strong-Initial-Password-2026!',
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('administrator creation was skipped')
            ->expectsOutputToContain('email field must be a valid email address')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_rejects_the_example_placeholder_password(): void
    {
        config()->set('ojt.bootstrap_admin', [
            'name' => 'Initial Administrator',
            'email' => 'initial.admin@example.com',
            'password' => 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD',
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('password is still a placeholder')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_is_idempotent_and_never_creates_a_second_administrator(): void
    {
        $existingAdministrator = User::factory()->superAdmin()->create();
        config()->set('ojt.bootstrap_admin', [
            'name' => 'Another Administrator',
            'email' => 'another.admin@example.com',
            'password' => 'Strong-Initial-Password-2026!',
        ]);

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertModelExists($existingAdministrator);
        $this->assertDatabaseMissing('users', ['email' => 'another.admin@example.com']);
    }
}
