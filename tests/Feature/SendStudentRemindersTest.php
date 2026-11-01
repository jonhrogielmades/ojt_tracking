<?php

namespace Tests\Feature;

use App\Models\DocumentRequirement;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\StudentReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendStudentRemindersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_sends_missing_dtr_low_progress_and_document_deadline_reminders(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-04 18:00:00'));
        $student = User::factory()->create(['program' => 'BSIT']);
        StudentProfile::factory()->for($student)->create([
            'ojt_start_date' => '2026-10-01',
            'ojt_end_date' => '2027-01-31',
            'work_days' => [1, 2, 3, 4, 5],
            'weekly_target_minutes' => 2400,
        ]);
        $requirement = DocumentRequirement::factory()->create([
            'name' => 'Supervisor evaluation',
            'program' => 'BSIT',
            'due_date' => '2026-11-09',
            'is_active' => true,
        ]);

        $this->artisan('students:send-reminders')->assertSuccessful();

        foreach (['missing_dtr', 'low_progress', 'document_deadline:'.$requirement->id] as $reminderType) {
            Notification::assertSentTo(
                $student,
                StudentReminderNotification::class,
                fn (StudentReminderNotification $notification): bool => $notification->reminderType === $reminderType,
            );
        }
    }
}
