<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\User;
use App\Services\AdminMfaService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$oiLQgld1b8U2YU.E.S53cOEJ5j2yOQ0j2ubKLfuc12/C9OWA78B.q';

    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(
        LoginRequest $request,
        AdminMfaService $mfaService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $administrator = User::query()
            ->administrators()
            ->where('email', $request->string('email')->toString())
            ->first();
        $passwordIsValid = Hash::check(
            $request->string('password')->toString(),
            $administrator?->password ?? self::DUMMY_PASSWORD_HASH,
        );

        if (
            $administrator === null
            || ! $administrator->is_active
            || ! $passwordIsValid
        ) {
            $auditLogger->record('auth.login.failed', null, $administrator, metadata: [
                'portal' => 'admin',
                'identifier_fingerprint' => $auditLogger->identifierFingerprint($request->string('email')->toString()),
            ]);

            throw ValidationException::withMessages([
                'email' => 'The provided administrator credentials are incorrect.',
            ]);
        }

        if (Hash::needsRehash($administrator->getAuthPassword())) {
            $administrator->forceFill([
                'password' => $request->string('password')->toString(),
            ])->save();
        }

        if ($administrator->mfa_enabled) {
            $request->session()->regenerate();
            $request->session()->put([
                'admin_mfa.pending_user_id' => $administrator->getKey(),
                'admin_mfa.remember' => $request->boolean('remember'),
            ]);
            $mfaService->sendCode($administrator);

            return to_route('admin.mfa.challenge');
        }

        Auth::guard('web')->login($administrator, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
