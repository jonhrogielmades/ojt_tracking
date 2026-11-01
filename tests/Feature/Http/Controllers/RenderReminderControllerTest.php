<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tests\TestCase;

class RenderReminderControllerTest extends TestCase
{
    public function test_endpoint_is_not_found_when_the_server_secret_is_not_configured(): void
    {
        config()->set('ojt.render.cron_secret', null);

        $this->postJson(route('internal.render.reminders'), headers: [
            'X-Render-Cron-Secret' => 'provided-secret',
        ])->assertNotFound();
    }

    public function test_endpoint_is_not_found_when_the_header_secret_is_missing_or_wrong(): void
    {
        config()->set('ojt.render.cron_secret', 'correct-secret');

        $this->postJson(route('internal.render.reminders'))->assertNotFound();
        $this->postJson(route('internal.render.reminders'), headers: [
            'X-Render-Cron-Secret' => 'wrong-secret',
        ])->assertNotFound();
    }

    public function test_valid_request_runs_only_the_student_reminder_command(): void
    {
        config()->set('ojt.render.cron_secret', 'correct-secret');
        Artisan::shouldReceive('call')
            ->once()
            ->with('students:send-reminders')
            ->andReturn(SymfonyCommand::SUCCESS);

        $this->postJson(route('internal.render.reminders'), headers: [
            'X-Render-Cron-Secret' => 'correct-secret',
        ])->assertOk()->assertExactJson(['status' => 'completed']);
    }

    public function test_command_failure_returns_service_unavailable(): void
    {
        config()->set('ojt.render.cron_secret', 'correct-secret');
        Artisan::shouldReceive('call')
            ->once()
            ->with('students:send-reminders')
            ->andReturn(SymfonyCommand::FAILURE);

        $this->postJson(route('internal.render.reminders'), headers: [
            'X-Render-Cron-Secret' => 'correct-secret',
        ])->assertStatus(503)->assertExactJson(['status' => 'failed']);
    }

    public function test_endpoint_is_rate_limited(): void
    {
        config()->set('ojt.render.cron_secret', 'correct-secret');
        Artisan::shouldReceive('call')
            ->times(3)
            ->with('students:send-reminders')
            ->andReturn(SymfonyCommand::SUCCESS);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson(route('internal.render.reminders'), headers: [
                'X-Render-Cron-Secret' => 'correct-secret',
            ])->assertOk();
        }

        $this->postJson(route('internal.render.reminders'), headers: [
            'X-Render-Cron-Secret' => 'correct-secret',
        ])->assertTooManyRequests();
    }
}
