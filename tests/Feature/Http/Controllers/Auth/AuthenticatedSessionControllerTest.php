<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credentials_authenticate_student_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'student_id' => '2310060-1',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'student_id' => ' 2310060-1 ',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_student(): void
    {
        User::factory()->create(['student_id' => '2310060-1']);

        $response = $this->post(route('login.store'), [
            'student_id' => '2310060-1',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The provided Student ID or password is incorrect.',
        ]);

        $this->assertGuest();
    }

    public function test_unknown_student_id_does_not_authenticate_student(): void
    {
        $response = $this->post(route('login.store'), [
            'student_id' => '9999999-2',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The provided Student ID or password is incorrect.',
        ]);

        $this->assertGuest();
    }

    public function test_login_rejects_student_id_outside_required_format(): void
    {
        $response = $this->post(route('login.store'), [
            'student_id' => 'OJT-2026-001',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The Student ID must use the format 2310060-1 or 2310060-2.',
        ]);

        $this->assertGuest();
    }

    public function test_admin_account_cannot_use_student_login(): void
    {
        User::factory()->admin()->create([
            'student_id' => '2310060-1',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'student_id' => '2310060-1',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('student_id');

        $this->assertGuest();
    }

    public function test_inactive_student_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'student_id' => '2310060-1',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'student_id' => '2310060-1',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('student_id');

        $this->assertGuest();
    }

    public function test_email_cannot_be_used_in_place_of_student_id(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('student_id');

        $this->assertGuest();
    }

    public function test_login_page_uses_student_id_instead_of_email(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Student ID');
        $response->assertSee('name="student_id"', false);
        $response->assertDontSee('name="email"', false);
    }

    public function test_login_rate_limit_is_scoped_to_normalized_student_id_and_ip_address(): void
    {
        foreach (range(1, 5) as $attempt) {
            $studentId = $attempt % 2 === 0 ? ' 1111111-1 ' : '1111111-1';

            $this->post(route('login.store'), [
                'student_id' => $studentId,
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $this->post(route('login.store'), [
            'student_id' => '1111111-1',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();

        $this->post(route('login.store'), [
            'student_id' => '2222222-2',
            'password' => 'incorrect-password',
        ])->assertRedirect();
    }
}
