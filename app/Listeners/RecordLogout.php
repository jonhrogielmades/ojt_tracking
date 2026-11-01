<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->record('auth.logout', $event->user, $event->user, metadata: [
            'guard' => $event->guard,
        ]);
    }
}
