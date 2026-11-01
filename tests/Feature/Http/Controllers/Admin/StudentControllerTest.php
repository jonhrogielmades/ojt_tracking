<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_list_and_filter_students_without_showing_admin_accounts(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'System Administrator']);
        User::factory()->admin()->create(['name' => 'Needle Administrator']);
        User::factory()->create(['name' => 'Needle Active', 'is_active' => true]);
        User::factory()->inactive()->create(['name' => 'Needle Inactive']);
        User::factory()->create(['name' => 'Different Student', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.students.index', [
            'q' => 'Needle',
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee('Needle Active');
        $response->assertDontSee('Needle Inactive');
        $response->assertDontSee('Needle Administrator');
        $response->assertDontSee('Different Student');
    }

    public function test_admin_can_create_active_student_and_cannot_assign_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Created Student',
            'email' => 'created@example.com',
            'student_id' => '2310060-1',
            'program' => 'BS Information Technology',
            'section' => '4A',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'is_active' => '1',
            'role' => UserRole::Admin->value,
        ]);

        $student = User::query()->where('email', 'created@example.com')->firstOrFail();

        $response->assertRedirectToRoute('admin.students.show', $student);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'student_id' => '2310060-1',
            'role' => UserRole::Student->value,
            'is_active' => true,
        ]);
        $this->assertTrue(Hash::check('StrongPass123!', $student->password));
    }

    public function test_admin_student_creation_rejects_invalid_student_id_format(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Invalid Student',
            'email' => 'invalid@example.com',
            'student_id' => 'OJT-2026-001',
            'program' => 'BS Information Technology',
            'section' => '4A',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The Student ID must use the format 2310060-1 or 2310060-2.',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    }

    public function test_admin_can_update_student_status_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'email' => 'before@example.com',
            'student_id' => '2310060-1',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.students.update', $student), [
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'student_id' => '2310060-2',
            'program' => 'BS Computer Science',
            'section' => '4B',
            'password' => 'UpdatedPass123!',
            'password_confirmation' => 'UpdatedPass123!',
            'is_active' => '0',
        ]);

        $response->assertRedirectToRoute('admin.students.show', $student);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Updated Student',
            'email' => 'updated@example.com',
            'student_id' => '2310060-2',
            'program' => 'BS Computer Science',
            'section' => '4B',
            'is_active' => false,
        ]);
        $this->assertTrue(Hash::check('UpdatedPass123!', $student->fresh()->password));
    }

    public function test_admin_archives_student_and_preserves_private_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $attachmentPath = "activity-attachments/{$student->id}/activity.pdf";
        $documentPath = "ojt-documents/{$student->id}/document.pdf";
        Storage::disk('local')->put($attachmentPath, 'activity');
        Storage::disk('local')->put($documentPath, 'document');
        ActivityLog::factory()->for($student)->create([
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => 'activity.pdf',
            'attachment_mime_type' => 'application/pdf',
            'attachment_size' => 8,
        ]);
        StudentDocument::factory()->for($student)->create([
            'storage_path' => $documentPath,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.students.destroy', $student));

        $response->assertRedirectToRoute('admin.students.index');
        $this->assertSoftDeleted($student);
        Storage::disk('local')->assertExists($attachmentPath);
        Storage::disk('local')->assertExists($documentPath);
    }

    public function test_student_cannot_access_admin_student_management(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.students.index'));

        $response->assertForbidden();
    }

    public function test_admin_account_is_not_exposed_as_student_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.students.show', $otherAdmin));

        $response->assertNotFound();
    }
}
