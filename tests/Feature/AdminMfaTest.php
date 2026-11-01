<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminMfaCodeNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_enabled_mfa_requires_emailed_code_after_valid_password(): void
    {
        Notification::fake();
        $administrator = User::factory()->admin()->withMfa()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('admin.mfa.challenge');
        $response->assertSessionHas('admin_mfa.pending_user_id', $administrator->id);
        $this->assertGuest();
        $administrator->refresh();
        $this->assertNotNull($administrator->mfa_code_hash);
        $this->assertNotNull($administrator->mfa_expires_at);
        Notification::assertSentTo($administrator, AdminMfaCodeNotification::class);
    }

    public function test_valid_mfa_code_authenticates_and_code_is_not_stored_in_plaintext(): void
    {
        Notification::fake();
        $administrator = User::factory()->admin()->withMfa()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $code = null;
        Notification::assertSentTo(
            $administrator,
            AdminMfaCodeNotification::class,
            function (AdminMfaCodeNotification $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            },
        );
        $administrator->refresh();
        $this->assertIsString($code);
        $this->assertNotSame($code, $administrator->mfa_code_hash);
        $this->assertTrue(Hash::check($code, $administrator->mfa_code_hash));

        $response = $this->post(route('admin.mfa.verify'), ['code' => $code]);

        $response->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($administrator);
        $this->assertNull($administrator->fresh()->mfa_code_hash);
    }

    public function test_current_password_is_required_to_enable_mfa(): void
    {
        $administrator = User::factory()->admin()->create(['password' => 'password']);

        $response = $this->actingAs($administrator)->put(route('admin.mfa.update'), [
            'enabled' => '1',
            'current_password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertFalse($administrator->fresh()->mfa_enabled);
    }

    public function test_valid_current_password_enables_mfa(): void
    {
        $administrator = User::factory()->admin()->create(['password' => 'password']);

        $response = $this->actingAs($administrator)->put(route('admin.mfa.update'), [
            'enabled' => '1',
            'current_password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertTrue($administrator->fresh()->mfa_enabled);
    }
}
