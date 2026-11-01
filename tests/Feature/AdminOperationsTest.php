<?php

namespace Tests\Feature;

use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Jobs\DispatchAnnouncement;
use App\Models\AcademicTerm;
use App\Models\Announcement;
use App\Models\CorrectionRequest;
use App\Models\DocumentRequirement;
use App\Models\DtrEntry;
use App\Models\PartnerCompany;
use App\Models\ReportTemplate;
use App\Models\StudentDocument;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Notifications\AnnouncementPublished;
use App\Notifications\CorrectionRequestReviewedNotification;
use App\Notifications\StudentDocumentReviewedNotification;
use App\Notifications\WeeklySubmissionReviewedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_create_an_active_academic_term_with_normalized_configuration(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $previousTerm = AcademicTerm::factory()->active()->create();

        $response = $this->actingAs($admin)->post(route('admin.academic_terms.store'), [
            'name' => 'First Semester 2026–2027',
            'code' => 'ay26-1',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-12-20',
            'required_hours' => '486',
            'programs_text' => "BSIT\nBSCS",
            'sections_text' => "4A\n4B",
            'holidays_text' => '2026-11-30|Bonifacio Day',
            'is_active' => '1',
        ]);

        $response->assertRedirectToRoute('admin.academic_terms.index');
        $term = AcademicTerm::query()->where('code', 'AY26-1')->firstOrFail();
        $this->assertTrue($term->is_active);
        $this->assertFalse($previousTerm->fresh()->is_active);
        $this->assertSame(29160, $term->required_minutes);
        $this->assertSame(['BSIT', 'BSCS'], $term->programs);
        $this->assertSame([['date' => '2026-11-30', 'name' => 'Bonifacio Day']], $term->holidays);
    }

    public function test_concurrent_active_term_conflict_returns_a_friendly_validation_error(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Event::listen('eloquent.creating: '.AcademicTerm::class, static function (): never {
            throw new QueryException(
                'mysql',
                'insert into academic_terms',
                [],
                new \PDOException(
                    "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1' for key 'academic_terms_single_active'",
                    23000,
                ),
            );
        });

        $response = $this->actingAs($admin)->post(route('admin.academic_terms.store'), [
            'name' => 'First Semester 2026–2027',
            'code' => 'AY26-1',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-12-20',
            'required_hours' => '486',
            'programs_text' => 'BSIT',
            'sections_text' => '4A',
            'holidays_text' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('is_active');
        $this->assertDatabaseMissing('academic_terms', ['code' => 'AY26-1']);
    }

    public function test_document_officer_can_review_private_student_document(): void
    {
        Notification::fake();
        Storage::fake('local');
        $officer = User::factory()->documentOfficer()->create();
        $student = User::factory()->create(['program' => 'BSIT']);
        $requirement = DocumentRequirement::factory()->create(['program' => 'BSIT']);
        $file = UploadedFile::fake()->create('evaluation.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($student)->post(route('documents.store'), [
            'document_requirement_id' => $requirement->id,
            'title' => 'Midterm evaluation',
            'category' => 'Evaluation',
            'document' => $file,
        ]);

        $uploadResponse->assertRedirectToRoute('documents.index');
        $document = StudentDocument::query()->firstOrFail();
        $reviewResponse = $this->actingAs($officer)->patch(route('admin.document_reviews.update', $document), [
            'review_status' => 'approved',
            'review_notes' => 'Verified against the signed original.',
        ]);

        $reviewResponse->assertRedirect();
        $this->assertDatabaseHas('student_documents', [
            'id' => $document->id,
            'review_status' => 'approved',
            'reviewer_id' => $officer->id,
        ]);
        $this->actingAs($student)->delete(route('documents.destroy', $document))->assertForbidden();
        Notification::assertSentTo($student, StudentDocumentReviewedNotification::class);
    }

    public function test_document_checklist_prefers_an_approved_upload_and_prevents_duplicate_approvals(): void
    {
        $officer = User::factory()->documentOfficer()->create();
        $student = User::factory()->create(['program' => 'BSIT']);
        $requirement = DocumentRequirement::factory()->create(['program' => 'BSIT']);
        $approvedDocument = StudentDocument::factory()->for($student)->create([
            'document_requirement_id' => $requirement->id,
            'review_status' => 'approved',
            'created_at' => now()->subDay(),
        ]);
        $newerPendingDocument = StudentDocument::factory()->for($student)->create([
            'document_requirement_id' => $requirement->id,
            'review_status' => 'pending',
            'created_at' => now(),
        ]);

        $checklistResponse = $this->actingAs($student)->get(route('documents.index'));
        $selectedDocument = $checklistResponse->viewData('latestDocumentsByRequirement')->get($requirement->id);
        $this->assertSame($approvedDocument->id, $selectedDocument->id);

        $this->actingAs($officer)->patch(route('admin.document_reviews.update', $newerPendingDocument), [
            'review_status' => 'approved',
            'review_notes' => 'Second approval should be rejected.',
        ])->assertSessionHasErrors('review_status');
        $this->assertSame('pending', $newerPendingDocument->refresh()->review_status);
    }

    public function test_coordinator_can_review_weekly_submissions_and_correction_requests(): void
    {
        Notification::fake();
        $coordinator = User::factory()->ojtCoordinator()->create();
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->submitted()->create();
        $approvedSubmission = WeeklySubmission::factory()->for($student)->approved()->create([
            'week_start' => now()->subWeek()->startOfWeek(),
        ]);
        $dtr = DtrEntry::factory()->for($student)->create(['weekly_submission_id' => $approvedSubmission->id]);
        $correctionRequest = CorrectionRequest::factory()->for($student)->for($dtr, 'dtrEntry')->create();

        $this->actingAs($coordinator)
            ->patch(route('admin.approval_queue.submissions.review', $submission), [
                'decision' => 'changes_requested',
                'reviewer_comments' => 'Clarify the work completed on Wednesday.',
            ])
            ->assertRedirectToRoute('admin.approval_queue');
        $this->actingAs($coordinator)
            ->patch(route('admin.approval_queue.corrections.review', $correctionRequest), [
                'decision' => 'approved',
                'reviewer_response' => 'Update the incorrect time-out and resubmit the week.',
            ])
            ->assertRedirectToRoute('admin.approval_queue');

        $this->assertSame(WeeklySubmissionStatus::ChangesRequested, $submission->fresh()->status);
        $this->assertSame(CorrectionRequestStatus::Approved, $correctionRequest->fresh()->status);
        $this->assertSame(WeeklySubmissionStatus::ChangesRequested, $approvedSubmission->fresh()->status);
        Notification::assertSentTo($student, WeeklySubmissionReviewedNotification::class);
        Notification::assertSentTo($student, CorrectionRequestReviewedNotification::class);
    }

    public function test_granular_roles_keep_auditor_read_only_and_document_officer_scoped(): void
    {
        $auditor = User::factory()->auditor()->create();
        $documentOfficer = User::factory()->documentOfficer()->create();

        $this->actingAs($auditor)->get(route('admin.risk_dashboard'))->assertOk();
        $this->actingAs($auditor)->get(route('admin.academic_terms.index'))->assertForbidden();
        $this->actingAs($auditor)->get(route('admin.approval_queue'))->assertForbidden();
        $this->actingAs($documentOfficer)->get(route('admin.document_reviews.index'))->assertOk();
        $this->actingAs($documentOfficer)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($documentOfficer)->get(route('admin.students.export'))->assertForbidden();
    }

    public function test_model_policies_enforce_the_same_capability_boundaries_as_routes(): void
    {
        $coordinator = User::factory()->ojtCoordinator()->create();
        $auditor = User::factory()->auditor()->create();
        $documentOfficer = User::factory()->documentOfficer()->create();
        $term = AcademicTerm::factory()->create();
        $company = PartnerCompany::factory()->create();
        $requirement = DocumentRequirement::factory()->create();
        $template = ReportTemplate::factory()->create();
        $announcement = Announcement::factory()->create();
        $document = StudentDocument::factory()->create();

        $this->assertTrue($coordinator->can('update', $term));
        $this->assertTrue($coordinator->can('update', $company));
        $this->assertTrue($coordinator->can('update', $template));
        $this->assertTrue($coordinator->can('publish', $announcement));
        $this->assertFalse($auditor->can('update', $term));
        $this->assertFalse($auditor->can('update', $company));
        $this->assertFalse($auditor->can('update', $template));
        $this->assertFalse($auditor->can('publish', $announcement));
        $this->assertTrue($documentOfficer->can('update', $requirement));
        $this->assertTrue($documentOfficer->can('review', $document));
        $this->assertFalse($documentOfficer->can('update', $term));
    }

    public function test_active_report_template_is_rendered_in_student_reports(): void
    {
        $student = User::factory()->create();
        ReportTemplate::factory()->active()->create([
            'institution_name' => 'North Valley State University',
            'monthly_title' => 'Official Monthly Internship Record',
        ]);

        $this->actingAs($student)
            ->get(route('reports.monthly'))
            ->assertOk()
            ->assertSee('North Valley State University')
            ->assertSee('Official Monthly Internship Record');
    }

    public function test_announcement_publish_is_queued_and_visible_to_targeted_students(): void
    {
        Queue::fake();
        $admin = User::factory()->superAdmin()->create();
        $student = User::factory()->create(['program' => 'BSIT']);

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Portfolio review schedule',
            'body' => 'Bring your signed portfolio on Friday.',
            'audience' => 'program',
            'audience_value' => 'BSIT',
            'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        ])->assertRedirectToRoute('admin.announcements.index');
        $announcement = Announcement::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.announcements.publish', $announcement))
            ->assertRedirectToRoute('admin.announcements.index');

        Queue::assertPushed(DispatchAnnouncement::class, fn (DispatchAnnouncement $job): bool => $job->announcementId === $announcement->id);
        $this->actingAs($student)->get(route('announcements.index'))->assertSee('Portfolio review schedule');
    }

    public function test_announcement_dispatch_is_idempotent_for_each_student_on_retry(): void
    {
        Notification::fake();
        $student = User::factory()->create(['program' => 'BSIT']);
        $announcement = Announcement::factory()->create([
            'audience' => 'program',
            'audience_value' => 'BSIT',
            'published_at' => now(),
            'sent_at' => null,
            'expires_at' => now()->addDay(),
        ]);
        $job = new DispatchAnnouncement($announcement->id);

        $job->handle();
        $announcement->update(['sent_at' => null]);
        $job->handle();

        Notification::assertSentToTimes($student, AnnouncementPublished::class, 1);
        $this->assertDatabaseHas('announcement_deliveries', [
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
        ]);
        $this->assertNotNull(DB::table('announcement_deliveries')
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $student->id)
            ->value('delivered_at'));
    }

    public function test_published_announcement_is_immutable_while_delivery_is_queued(): void
    {
        Queue::fake();
        $admin = User::factory()->superAdmin()->create();
        $announcement = Announcement::factory()->create([
            'title' => 'Original announcement',
            'published_at' => null,
            'sent_at' => null,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.announcements.publish', $announcement))
            ->assertRedirectToRoute('admin.announcements.index');
        $this->actingAs($admin)
            ->patch(route('admin.announcements.update', $announcement), [
                'title' => 'Changed after publishing',
                'body' => 'This content must not replace the queued snapshot.',
                'audience' => 'all',
                'audience_value' => null,
                'expires_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('announcement');
        $this->actingAs($admin)
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertSessionHasErrors('announcement');
        $this->actingAs($admin)
            ->post(route('admin.announcements.publish', $announcement))
            ->assertSessionHas('status', 'This announcement was already published and cannot be queued again.');

        $this->assertSame('Original announcement', $announcement->fresh()->title);
        $this->assertModelExists($announcement);
        Queue::assertPushed(DispatchAnnouncement::class, 1);
    }

    public function test_admin_can_safely_retry_pending_announcement_delivery_but_not_expired_delivery(): void
    {
        Queue::fake();
        $admin = User::factory()->superAdmin()->create();
        $pending = Announcement::factory()->create([
            'published_at' => now(),
            'sent_at' => null,
            'expires_at' => now()->addDay(),
        ]);
        $expired = Announcement::factory()->create([
            'published_at' => now()->subDays(2),
            'sent_at' => null,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.announcements.retry', $pending))
            ->assertRedirectToRoute('admin.announcements.index')
            ->assertSessionHas('status');
        $this->actingAs($admin)
            ->post(route('admin.announcements.retry', $expired))
            ->assertSessionHasErrors('announcement');

        Queue::assertPushed(
            DispatchAnnouncement::class,
            1,
        );
        $this->actingAs($admin)
            ->get(route('admin.announcements.index'))
            ->assertSee('Retry delivery')
            ->assertSee('Expired — undelivered');
    }
}
