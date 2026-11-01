<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\MfaChallengeRequest;
use App\Models\User;
use App\Services\AdminMfaService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfaChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $administrator = $this->pendingAdministrator($request);

        if ($administrator === null) {
            return to_route('admin.login')->with('status', 'Your verification session expired. Please sign in again.');
        }

        return view('admin.auth.mfa-challenge', [
            'maskedEmail' => Str::mask($administrator->email, '*', 2, max(1, strlen($administrator->email) - 6)),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(
        MfaChallengeRequest $request,
        AdminMfaService $mfaService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $administrator = $this->pendingAdministrator($request);

        if ($administrator === null) {
            return to_route('admin.login')->with('status', 'Your verification session expired. Please sign in again.');
        }

        if (! $mfaService->verify($administrator, $request->string('code')->toString())) {
            $auditLogger->record('auth.mfa.failed', null, $administrator);

            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or has expired.',
            ]);
        }

        $remember = (bool) $request->session()->pull('admin_mfa.remember', false);
        $request->session()->forget('admin_mfa.pending_user_id');
        Auth::guard('web')->login($administrator, $remember);
        $request->session()->regenerate();
        $auditLogger->record('auth.mfa.succeeded', $administrator, $administrator);

        return to_route('admin.dashboard');
    }

    public function resend(Request $request, AdminMfaService $mfaService): RedirectResponse
    {
        $administrator = $this->pendingAdministrator($request);

        if ($administrator === null) {
            return to_route('admin.login')->with('status', 'Your verification session expired. Please sign in again.');
        }

        $mfaService->sendCode($administrator);

        return back()->with('status', 'A new verification code was sent.');
    }

    private function pendingAdministrator(Request $request): ?User
    {
        $userId = $request->session()->get('admin_mfa.pending_user_id');

        if ($userId === null) {
            return null;
        }

        return User::query()->administrators()->where('is_active', true)->find($userId);
    }
}
