<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_securely_creates_active_admin_account(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'System Administrator')
            ->expectsQuestion('Administrator email', 'ADMIN@EXAMPLE.COM')
            ->expectsQuestion('Password', 'StrongPass123!')
            ->expectsQuestion('Confirm password', 'StrongPass123!')
            ->doesntExpectOutputToContain('StrongPass123!')
            ->expectsOutput('Administrator account created for admin@example.com.')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->student_id);
        $this->assertTrue(Hash::check('StrongPass123!', $admin->password));
    }

    public function test_command_rejects_duplicate_admin_email(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Second Administrator',
            '--email' => 'ADMIN@EXAMPLE.COM',
        ])
            ->expectsQuestion('Password', 'StrongPass123!')
            ->expectsQuestion('Confirm password', 'StrongPass123!')
            ->expectsOutput('The email has already been taken.')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
    }
}
