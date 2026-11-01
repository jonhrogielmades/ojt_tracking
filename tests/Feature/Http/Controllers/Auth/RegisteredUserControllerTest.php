<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_registration_creates_and_authenticates_student(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ana Student',
            'email' => 'ANA@EXAMPLE.COM',
            'student_id' => '2310060-1',
            'program' => 'BS Information Technology',
            'section' => '4A',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertRedirectToRoute('dashboard');
        $response->assertSessionHas('status');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Ana Student',
            'email' => 'ana@example.com',
            'student_id' => '2310060-1',
            'program' => 'BS Information Technology',
            'section' => '4A',
            'role' => UserRole::Student->value,
            'is_active' => true,
        ]);
    }

    public function test_registration_rejects_duplicate_student_id(): void
    {
        User::factory()->create(['student_id' => '2310060-2']);

        $response = $this->post(route('register.store'), [
            'name' => 'Second Student',
            'email' => 'second@example.com',
            'student_id' => '2310060-2',
            'program' => 'BS Computer Science',
            'section' => '4B',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('student_id');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    #[DataProvider('invalidStudentIds')]
    public function test_registration_rejects_student_id_outside_required_format(string $studentId): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Invalid Student',
            'email' => 'invalid@example.com',
            'student_id' => $studentId,
            'program' => 'BS Computer Science',
            'section' => '4B',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The Student ID must use the format 2310060-1 or 2310060-2.',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStudentIds(): array
    {
        return [
            'six digits' => ['231006-1'],
            'eight digits' => ['23100600-1'],
            'invalid suffix' => ['2310060-3'],
            'missing hyphen' => ['23100601'],
            'letters included' => ['ABC0060-1'],
        ];
    }
}
