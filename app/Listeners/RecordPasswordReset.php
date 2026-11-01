<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;

class RecordPasswordReset
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->record('auth.password.reset', $event->user, $event->user);
    }
}
