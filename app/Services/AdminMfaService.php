<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AdminMfaCodeNotification;
use Illuminate\Support\Facades\Hash;

class AdminMfaService
{
    public function sendCode(User $administrator): void
    {
        abort_unless($administrator->isAdmin() && $administrator->is_active, 403);

        $code = (string) random_int(100000, 999999);
        $expirationMinutes = (int) config('ojt.mfa.expiration_minutes', 10);

        $administrator->forceFill([
            'mfa_code_hash' => Hash::make($code),
            'mfa_expires_at' => now()->addMinutes($expirationMinutes),
            'mfa_last_sent_at' => now(),
        ])->save();

        $administrator->notify(new AdminMfaCodeNotification($code, $expirationMinutes));
    }

    public function verify(User $administrator, string $code): bool
    {
        if (
            ! $administrator->mfa_enabled
            || $administrator->mfa_code_hash === null
            || $administrator->mfa_expires_at === null
            || $administrator->mfa_expires_at->isPast()
            || ! Hash::check($code, $administrator->mfa_code_hash)
        ) {
            return false;
        }

        $this->clearCode($administrator);

        return true;
    }

    public function clearCode(User $administrator): void
    {
        $administrator->forceFill([
            'mfa_code_hash' => null,
            'mfa_expires_at' => null,
        ])->save();
    }
}
