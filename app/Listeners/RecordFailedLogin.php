<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $identifier = (string) ($event->credentials['email'] ?? $event->credentials['student_id'] ?? 'unknown');
        $subject = $event->user instanceof User ? $event->user : null;

        $this->auditLogger->record('auth.login.failed', null, $subject, metadata: [
            'guard' => $event->guard,
            'identifier_fingerprint' => $this->auditLogger->identifierFingerprint($identifier),
        ]);
    }
}
