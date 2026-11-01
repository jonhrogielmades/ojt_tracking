<?php

namespace Tests\Feature;

use App\Models\CorrectionRequest;
use App\Models\DatabaseBackup;
use App\Models\StudentDocument;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Notifications\StudentReminderNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAlertControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_coordinator_sees_actionable_approval_and_document_alerts(): void
    {
        $coordinator = User::factory()->ojtCoordinator()->create();
        $student = User::factory()->create(['name' => 'Action Needed Student']);
        WeeklySubmission::factory()->for($student)->submitted()->create();
        CorrectionRequest::factory()->for($student)->create();
        StudentDocument::factory()->for($student)->create([
            'title' => 'Pending Evaluation',
            'review_status' => 'pending',
        ]);

        $response = $this->actingAs($coordinator)->get(route('admin.alerts.index'));

        $response->assertOk()
            ->assertSee('Notification center')
            ->assertSee('Action Needed Student')
            ->assertSee('Pending Evaluation')
            ->assertSee('Open approval queue')
            ->assertSee('Review documents');
    }

    public function test_auditor_sees_system_signals_without_operational_student_details(): void
    {
        $auditor = User::factory()->auditor()->create();
        $student = User::factory()->create(['name' => 'Hidden Operational Student']);
        WeeklySubmission::factory()->for($student)->submitted()->create();
        StudentDocument::factory()->for($student)->create(['review_status' => 'pending']);
        DatabaseBackup::factory()->create(['status' => 'completed']);
        DB::table('failed_jobs')->insert([
            'uuid' => 'd662b6da-5798-43c9-ab33-05d448327fe6',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($auditor)->get(route('admin.alerts.index'));

        $response->assertOk()
            ->assertSee('Failed queue jobs')
            ->assertSee('Latest backup: Completed')
            ->assertDontSee('Hidden Operational Student')
            ->assertDontSee('Open approval queue')
            ->assertDontSee('Review documents');
    }

    public function test_administrator_can_read_only_notifications_owned_by_their_account(): void
    {
        $administrator = User::factory()->superAdmin()->create();
        $otherAdministrator = User::factory()->superAdmin()->create();
        $administrator->notifyNow(new StudentReminderNotification(
            'admin_test',
            'Backup review requested',
            'Check the latest backup verification result.',
            '/admin/backups',
        ));
        $otherAdministrator->notifyNow(new StudentReminderNotification(
            'admin_test',
            'Private administrator message',
            'This notification belongs to another administrator.',
            '/admin',
        ));
        $ownedNotification = $administrator->notifications()->firstOrFail();
        $otherNotification = $otherAdministrator->notifications()->firstOrFail();

        $this->actingAs($administrator)
            ->get(route('admin.alerts.index'))
            ->assertOk()
            ->assertSee('Backup review requested')
            ->assertDontSee('Private administrator message');
        $this->actingAs($administrator)
            ->patch(route('admin.alerts.read', $ownedNotification->id))
            ->assertRedirect();
        $this->actingAs($administrator)
            ->patch(route('admin.alerts.read', $otherNotification->id))
            ->assertNotFound();

        $this->assertNotNull($ownedNotification->refresh()->read_at);
        $this->assertNull($otherNotification->refresh()->read_at);
    }

    public function test_student_is_forbidden_and_guest_is_redirected_from_admin_alerts(): void
    {
        $student = User::factory()->create();

        $this->get(route('admin.alerts.index'))->assertRedirectToRoute('admin.login');
        $this->actingAs($student)->get(route('admin.alerts.index'))->assertForbidden();
    }

    public function test_administrator_can_mark_all_owned_notifications_as_read(): void
    {
        $administrator = User::factory()->ojtCoordinator()->create();
        $administrator->notifyNow(new StudentReminderNotification(
            'admin_test',
            'First message',
            'First message body.',
            '/admin',
        ));
        $administrator->notifyNow(new StudentReminderNotification(
            'admin_test',
            'Second message',
            'Second message body.',
            '/admin',
        ));

        $response = $this->actingAs($administrator)->patch(route('admin.alerts.read-all'));

        $response->assertRedirect();
        $this->assertSame(0, $administrator->unreadNotifications()->count());
    }
}
