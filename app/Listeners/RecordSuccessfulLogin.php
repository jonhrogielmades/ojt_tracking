<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->record('auth.login.succeeded', $event->user, $event->user, metadata: [
            'guard' => $event->guard,
            'role' => $event->user->role,
        ]);
    }
}
