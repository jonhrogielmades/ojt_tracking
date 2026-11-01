<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_admin_credentials_authenticate_and_redirect_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => ' ADMIN@EXAMPLE.COM ',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_invalid_admin_password_does_not_authenticate(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_account_cannot_use_admin_login(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_admin_cannot_login(): void
    {
        User::factory()->admin()->inactive()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_page_uses_email_and_links_to_student_login(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
        $response->assertSee('Administrator');
        $response->assertSee('name="email"', false);
        $response->assertSee(route('login'), false);
    }
}
