<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QueuedResetPasswordNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_and_administrator_can_request_password_reset_links(): void
    {
        Notification::fake();
        $student = User::factory()->create(['email' => 'student@example.com']);
        $administrator = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('password.email'), ['email' => $student->email])
            ->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => $administrator->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($student, QueuedResetPasswordNotification::class);
        Notification::assertSentTo($administrator, QueuedResetPasswordNotification::class);
    }

    public function test_unknown_email_receives_generic_recovery_response(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

        $response->assertSessionHas('status', 'If an account matches that email, a password reset link has been sent.');
        Notification::assertNothingSent();
    }

    public function test_valid_token_resets_student_password_and_redirects_to_student_login(): void
    {
        $student = User::factory()->create(['password' => 'old-password']);
        $token = Password::broker()->createToken($student);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $student->email,
            'password' => 'NewStrongPass123!',
            'password_confirmation' => 'NewStrongPass123!',
        ]);

        $response->assertRedirectToRoute('login');
        $this->assertTrue(Hash::check('NewStrongPass123!', $student->fresh()->password));
    }

    public function test_valid_token_resets_administrator_password_and_redirects_to_admin_login(): void
    {
        $administrator = User::factory()->admin()->create(['password' => 'old-password']);
        $token = Password::broker()->createToken($administrator);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $administrator->email,
            'password' => 'NewStrongPass123!',
            'password_confirmation' => 'NewStrongPass123!',
        ]);

        $response->assertRedirectToRoute('admin.login');
        $this->assertTrue(Hash::check('NewStrongPass123!', $administrator->fresh()->password));
    }
}
