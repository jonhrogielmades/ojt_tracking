<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\QueuedResetPasswordNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminStudentImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_preview_and_confirm_valid_csv_students_with_password_setup_links(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $csv = implode("\n", [
            'name,email,student_id,program,section',
            'Ana Reyes,ana@example.test,2310060-1,BSIT,4A',
            'Ben Cruz,ben@example.test,2310060-2,BSCS,4B',
        ]);

        $previewResponse = $this->actingAs($admin)->post(route('admin.students.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

        $previewResponse->assertOk()->assertSee('2 valid of 2 row(s)');
        preg_match('/name="token" value="([^"]+)"/', $previewResponse->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $confirmResponse = $this->actingAs($admin)->post(route('admin.students.import.store'), [
            'token' => $matches[1],
        ]);

        $confirmResponse->assertRedirectToRoute('admin.students.index');
        $ana = User::query()->where('email', 'ana@example.test')->firstOrFail();
        $ben = User::query()->where('email', 'ben@example.test')->firstOrFail();
        $this->assertSame(UserRole::Student, $ana->role);
        $this->assertSame(UserRole::Student, $ben->role);
        Notification::assertSentTo($ana, QueuedResetPasswordNotification::class);
        Notification::assertSentTo($ben, QueuedResetPasswordNotification::class);
    }

    public function test_csv_preview_excludes_invalid_and_duplicate_rows(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $csv = implode("\n", [
            'name,email,student_id,program,section',
            'Ana Reyes,ana@example.test,2310060-1,BSIT,4A',
            'Duplicate Email,ana@example.test,2310061-1,BSIT,4A',
            'Bad ID,bad@example.test,OJT-001,BSIT,4A',
        ]);

        $this->actingAs($admin)->post(route('admin.students.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertOk()
            ->assertSee('1 valid of 3 row(s)')
            ->assertSee('Email is duplicated on line 2.')
            ->assertSee('The Student ID must use the format 2310060-1 or 2310060-2.');
    }

    public function test_auditor_cannot_import_students_but_can_export_read_only_data(): void
    {
        $auditor = User::factory()->auditor()->create();

        $this->actingAs($auditor)->get(route('admin.students.import'))->assertForbidden();
        $this->actingAs($auditor)->get(route('admin.students.export'))->assertOk();
    }
}
