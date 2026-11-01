<?php

namespace Tests\Feature;

use App\Actions\ReviewCorrectionRequestAction;
use App\Enums\CorrectionRequestStatus;
use App\Enums\WeeklySubmissionStatus;
use App\Models\CorrectionRequest;
use App\Models\DtrEntry;
use App\Models\User;
use App\Models\WeeklySubmission;
use App\Notifications\CorrectionRequestReviewedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CorrectionRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_request_a_correction_for_owned_approved_dtr(): void
    {
        $student = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($student)->approved()->create();
        $entry = DtrEntry::factory()->for($student)->for($submission, 'weeklySubmission')->create();

        $response = $this->actingAs($student)->post(route('corrections.store', $entry), [
            'reason' => 'The recorded time out should be 5:30 PM due to an approved extension.',
        ]);

        $response->assertRedirectToRoute('corrections.index');
        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $student->id,
            'dtr_entry_id' => $entry->id,
            'status' => CorrectionRequestStatus::Pending->value,
        ]);
        $this->actingAs($student)->get(route('corrections.index'))
            ->assertOk()
            ->assertSee('approved extension')
            ->assertSee('Pending');
    }

    public function test_student_cannot_request_correction_for_another_students_entry(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $submission = WeeklySubmission::factory()->for($otherStudent)->approved()->create();
        $entry = DtrEntry::factory()->for($otherStudent)->for($submission, 'weeklySubmission')->create();

        $this->actingAs($student)->post(route('corrections.store', $entry), [
            'reason' => 'This is a sufficiently detailed but unauthorized correction reason.',
        ])->assertNotFound();
    }

    public function test_approving_correction_unlocks_week_and_notifies_student(): void
    {
        Notification::fake();
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $submission = WeeklySubmission::factory()->for($student)->approved()->create();
        $entry = DtrEntry::factory()->for($student)->for($submission, 'weeklySubmission')->create();
        $correction = CorrectionRequest::factory()->for($student)->for($entry, 'dtrEntry')->create();

        $reviewed = app(ReviewCorrectionRequestAction::class)->execute(
            $correction,
            $admin,
            CorrectionRequestStatus::Approved,
            'Update the time out and resubmit the week.',
        );

        $this->assertSame(CorrectionRequestStatus::Approved, $reviewed->status);
        $this->assertSame(WeeklySubmissionStatus::ChangesRequested, $submission->refresh()->status);
        Notification::assertSentTo($student, CorrectionRequestReviewedNotification::class);
    }
}
