<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRoleGovernanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_each_administrator_role_has_separate_permissions(): void
    {
        $student = User::factory()->create();
        $coordinator = User::factory()->ojtCoordinator()->create();
        $auditor = User::factory()->auditor()->create();
        $documentOfficer = User::factory()->documentOfficer()->create();
        $superAdministrator = User::factory()->superAdmin()->create();

        $this->actingAs($coordinator)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($auditor)->get(route('admin.students.show', $student))->assertOk();
        $this->actingAs($auditor)->get(route('admin.students.edit', $student))->assertForbidden();
        $this->actingAs($documentOfficer)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($documentOfficer)->get(route('admin.system-health'))->assertForbidden();
        $this->actingAs($superAdministrator)->get(route('admin.administrators.index'))->assertOk();
        $this->actingAs($superAdministrator)->get(route('admin.backups.index'))->assertOk();
    }

    public function test_super_administrator_can_create_a_scoped_administrator_account(): void
    {
        $superAdministrator = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdministrator)->post(route('admin.administrators.store'), [
            'name' => 'Document Reviewer',
            'email' => 'reviewer@example.com',
            'role' => UserRole::DocumentOfficer->value,
            'is_active' => '1',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $administrator = User::query()->where('email', 'reviewer@example.com')->firstOrFail();
        $response->assertRedirectToRoute('admin.administrators.edit', $administrator);
        $this->assertSame(UserRole::DocumentOfficer, $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertNull($administrator->student_id);
    }

    public function test_admin_navigation_only_shows_links_allowed_for_the_current_role(): void
    {
        $auditor = User::factory()->auditor()->create();
        $documentOfficer = User::factory()->documentOfficer()->create();

        $auditorResponse = $this->actingAs($auditor)->get(route('admin.dashboard'));
        $auditorResponse->assertOk();
        $auditorResponse->assertSee(route('admin.students.export'), false);
        $auditorResponse->assertSee(route('admin.audit.index'), false);
        $auditorResponse->assertSee(route('admin.risk_dashboard'), false);
        $auditorResponse->assertDontSee(route('admin.students.import'), false);
        $auditorResponse->assertDontSee(route('admin.document_reviews.index'), false);

        $documentResponse = $this->actingAs($documentOfficer)->get(route('admin.dashboard'));
        $documentResponse->assertOk();
        $documentResponse->assertSee(route('admin.document_reviews.index'), false);
        $documentResponse->assertDontSee(route('admin.students.export'), false);
        $documentResponse->assertDontSee(route('admin.risk_dashboard'), false);
    }

    public function test_student_archive_preserves_records_and_can_be_restored(): void
    {
        Storage::fake('local');
        $coordinator = User::factory()->ojtCoordinator()->create();
        $student = User::factory()->create();
        $documentPath = "ojt-documents/{$student->id}/resume.pdf";
        Storage::disk('local')->put($documentPath, 'private document');

        $response = $this->actingAs($coordinator)->delete(route('admin.students.destroy', $student));

        $response->assertRedirectToRoute('admin.students.index');
        $this->assertSoftDeleted($student);
        Storage::disk('local')->assertExists($documentPath);

        $restoreResponse = $this->actingAs($coordinator)->patch(route('admin.students.restore', [
            'studentId' => $student->id,
        ]));

        $restoreResponse->assertRedirectToRoute('admin.students.show', $student);
        $this->assertNotSoftDeleted($student);
        Storage::disk('local')->assertExists($documentPath);
    }

    public function test_only_super_administrator_can_purge_archived_student_and_private_files(): void
    {
        Storage::fake('private-documents');
        Storage::fake('private-attachments');
        config()->set('ojt.documents.disk', 'private-documents');
        config()->set('ojt.attachments.disk', 'private-attachments');
        $coordinator = User::factory()->ojtCoordinator()->create();
        $superAdministrator = User::factory()->superAdmin()->create();
        $student = User::factory()->create();
        $documentPath = "ojt-documents/{$student->id}/resume.pdf";
        $attachmentPath = "activity-attachments/{$student->id}/evidence.pdf";
        Storage::disk('private-documents')->put($documentPath, 'private document');
        Storage::disk('private-attachments')->put($attachmentPath, 'private evidence');
        $student->delete();

        $this->actingAs($coordinator)->delete(route('admin.students.purge', [
            'studentId' => $student->id,
        ]))->assertForbidden();

        $response = $this->actingAs($superAdministrator)->delete(route('admin.students.purge', [
            'studentId' => $student->id,
        ]));

        $response->assertRedirectToRoute('admin.students.index', ['status' => 'archived']);
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        Storage::disk('private-documents')->assertMissing($documentPath);
        Storage::disk('private-attachments')->assertMissing($attachmentPath);
    }
}
