<?php

namespace Tests\Feature;

use App\Actions\ReviewWeeklySubmissionAction;
use App\Enums\WeeklySubmissionStatus;
use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Notifications\WeeklySubmissionReviewedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeeklySubmissionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_submit_owned_dtr_entries_for_a_week(): void
    {
        $student = User::factory()->create();
        $entry = DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-27']);
        ActivityLog::factory()->for($student)->create([
            'activity_date' => '2026-10-27',
            'tasks_completed' => 'Documented the deployment process',
        ]);

        $this->actingAs($student)->get(route('submissions.create', ['week' => '2026-10-26']))
            ->assertOk()
            ->assertSee('Documented the deployment process');

        $response = $this->actingAs($student)->post(route('submissions.store'), [
            'week_start' => '2026-10-26',
            'summary' => 'Completed the assigned deployment work and documented the process.',
            'student_notes' => 'Ready for review.',
            'dtr_entry_ids' => [$entry->id],
            'intent' => 'submit',
        ]);

        $submission = WeeklySubmission::query()->sole();
        $response->assertRedirectToRoute('submissions.show', $submission);
        $this->assertSame(WeeklySubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertDatabaseHas('dtr_entries', [
            'id' => $entry->id,
            'weekly_submission_id' => $submission->id,
        ]);
        $this->actingAs($student)->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('Completed the assigned deployment work')
            ->assertSee('Submitted');
        $this->actingAs($student)->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Weekly submissions');
    }

    public function test_submitted_dtr_cannot_be_edited_or_deleted(): void
    {
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->submitted()->create();
        $entry = DtrEntry::factory()->for($student)->for($submission, 'weeklySubmission')->create([
            'work_date' => '2026-10-27',
            'notes' => 'Original',
        ]);

        $this->actingAs($student)->get(route('dtr.edit', $entry))
            ->assertRedirectToRoute('dtr.index')
            ->assertSessionHasErrors('dtr');
        $this->actingAs($student)->patch(route('dtr.update', $entry), $this->validDtrPayload())
            ->assertRedirectToRoute('dtr.index')
            ->assertSessionHasErrors('dtr');
        $this->actingAs($student)->delete(route('dtr.destroy', $entry))
            ->assertRedirectToRoute('dtr.index')
            ->assertSessionHasErrors('dtr');

        $this->assertDatabaseHas('dtr_entries', ['id' => $entry->id, 'notes' => 'Original']);
    }

    public function test_changes_requested_unlocks_attached_dtr_for_editing(): void
    {
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->create([
            'status' => WeeklySubmissionStatus::ChangesRequested,
        ]);
        $entry = DtrEntry::factory()->for($student)->for($submission, 'weeklySubmission')->create([
            'work_date' => '2026-10-27',
        ]);

        $this->actingAs($student)->patch(route('dtr.update', $entry), $this->validDtrPayload())
            ->assertRedirectToRoute('dtr.index');

        $this->assertDatabaseHas('dtr_entries', ['id' => $entry->id, 'notes' => 'Corrected note']);
    }

    public function test_moving_an_entry_out_of_an_editable_week_detaches_it_from_the_draft(): void
    {
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->create([
            'week_start' => '2026-10-26',
            'status' => WeeklySubmissionStatus::Draft,
        ]);
        $entry = DtrEntry::factory()->for($student)->for($submission, 'weeklySubmission')->create([
            'work_date' => '2026-10-27',
        ]);

        $this->actingAs($student)->patch(route('dtr.update', $entry), [
            ...$this->validDtrPayload(),
            'work_date' => '2026-10-20',
        ])->assertRedirectToRoute('dtr.index');

        $this->assertNull($entry->refresh()->weekly_submission_id);
        $this->assertSame('2026-10-20', $entry->work_date->toDateString());
    }

    public function test_submitted_week_rejects_new_and_moved_dtr_entries(): void
    {
        $student = User::factory()->create();
        WeeklySubmission::factory()->for($student)->submitted()->create([
            'week_start' => '2026-10-26',
        ]);
        $unlockedEntry = DtrEntry::factory()->for($student)->create([
            'work_date' => '2026-10-20',
        ]);
        $orphanedLockedEntry = DtrEntry::factory()->for($student)->create([
            'work_date' => '2026-10-29',
        ]);

        $this->actingAs($student)->post(route('dtr.store'), $this->validDtrPayload())
            ->assertSessionHasErrors('work_date');
        $this->actingAs($student)->patch(route('dtr.update', $unlockedEntry), $this->validDtrPayload())
            ->assertSessionHasErrors('work_date');
        $this->actingAs($student)->delete(route('dtr.destroy', $orphanedLockedEntry))
            ->assertSessionHasErrors('dtr');

        $this->assertDatabaseMissing('dtr_entries', [
            'user_id' => $student->id,
            'work_date' => '2026-10-27',
        ]);
        $this->assertSame('2026-10-20', $unlockedEntry->refresh()->work_date->toDateString());
        $this->assertDatabaseHas('dtr_entries', ['id' => $orphanedLockedEntry->id]);
    }

    public function test_offline_sync_keeps_draft_pending_when_week_is_submitted(): void
    {
        $student = User::factory()->create();
        WeeklySubmission::factory()->for($student)->submitted()->create([
            'week_start' => '2026-10-26',
        ]);

        $response = $this->actingAs($student)->postJson(route('sync.dtr'), [
            ...$this->validDtrPayload(),
            'idempotency_key' => 'd1acfa07-98ce-44dd-a544-bfc2aa39011d',
        ]);

        $response->assertConflict()
            ->assertJsonPath('status', 'conflict')
            ->assertJsonPath('conflict_type', 'weekly_submission');
        $this->assertDatabaseMissing('dtr_entries', [
            'user_id' => $student->id,
            'work_date' => '2026-10-27',
        ]);
    }

    public function test_submission_requires_every_dtr_from_the_week(): void
    {
        $student = User::factory()->create();
        $selectedEntry = DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-27']);
        DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-28']);

        $this->actingAs($student)->post(route('submissions.store'), [
            'week_start' => '2026-10-26',
            'summary' => 'A complete summary that is ready for administrative review.',
            'dtr_entry_ids' => [$selectedEntry->id],
            'intent' => 'submit',
        ])->assertSessionHasErrors('dtr_entry_ids');

        $this->assertDatabaseCount('weekly_submissions', 0);
    }

    public function test_student_cannot_view_another_students_submission(): void
    {
        $student = User::factory()->create();
        $otherSubmission = WeeklySubmission::factory()->create();

        $this->actingAs($student)->get(route('submissions.show', $otherSubmission))->assertNotFound();
    }

    public function test_administrator_cannot_access_student_submission_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('submissions.index'))->assertForbidden();
    }

    public function test_review_action_updates_status_and_queues_database_notification(): void
    {
        Notification::fake();
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $submission = WeeklySubmission::factory()->for($student)->submitted()->create();

        $reviewed = app(ReviewWeeklySubmissionAction::class)->execute(
            $submission,
            $admin,
            WeeklySubmissionStatus::Approved,
            'Complete and accurate.',
        );

        $this->assertSame(WeeklySubmissionStatus::Approved, $reviewed->status);
        $this->assertSame($admin->id, $reviewed->reviewed_by);
        Notification::assertSentTo($student, WeeklySubmissionReviewedNotification::class);
    }

    public function test_student_cannot_invoke_submission_review_action(): void
    {
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->submitted()->create();

        $this->expectException(AuthorizationException::class);

        app(ReviewWeeklySubmissionAction::class)->execute(
            $submission,
            $student,
            WeeklySubmissionStatus::Approved,
        );
    }

    /**
     * @return array<string, string>
     */
    private function validDtrPayload(): array
    {
        return [
            'work_date' => '2026-10-27',
            'time_in' => '08:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'time_out' => '17:00',
            'notes' => 'Corrected note',
        ];
    }
}
