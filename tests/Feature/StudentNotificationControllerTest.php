<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\StudentReminderNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentNotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_view_and_mark_owned_notification_as_read(): void
    {
        $student = User::factory()->create();
        $student->notify(new StudentReminderNotification(
            'missing_dtr',
            'DTR entry may be missing',
            'Add your attendance record.',
            '/dtr/create',
        ));
        $notification = $student->unreadNotifications()->sole();

        $this->actingAs($student)->get(route('student.notifications'))
            ->assertOk()
            ->assertSee('DTR entry may be missing');
        $this->actingAs($student)->patch(route('student.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_student_cannot_mark_another_students_notification_as_read(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $otherStudent->notify(new StudentReminderNotification(
            'low_progress',
            'Weekly progress is below target',
            'Review your target.',
            '/progress',
        ));
        $notification = $otherStudent->unreadNotifications()->sole();

        $this->actingAs($student)
            ->patch(route('student.notifications.read', $notification->id))
            ->assertNotFound();
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $student = User::factory()->create();

        foreach (range(1, 2) as $number) {
            $student->notify(new StudentReminderNotification(
                'reminder-'.$number,
                'Reminder '.$number,
                'Message',
                '/progress',
            ));
        }

        $this->actingAs($student)->patch(route('student.notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $student->unreadNotifications()->count());
    }
}
