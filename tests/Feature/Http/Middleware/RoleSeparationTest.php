<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoleSeparationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_admin_request_redirects_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirectToRoute('admin.login');
    }

    public function test_student_is_forbidden_from_admin_area(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_from_student_area(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertForbidden();
    }

    public function test_inactive_authenticated_student_is_logged_out_on_next_request(): void
    {
        $student = User::factory()->inactive()->create();

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_inactive_authenticated_admin_is_logged_out_on_next_request(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirectToRoute('admin.login');

        $this->assertGuest();
    }
}
