<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMfaSettingsRequest;
use App\Services\AdminMfaService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MfaSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.mfa');
    }

    public function update(
        UpdateMfaSettingsRequest $request,
        AdminMfaService $mfaService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $administrator = $request->user();
        $wasEnabled = $administrator->mfa_enabled;
        $enabled = $request->boolean('enabled');

        $administrator->update(['mfa_enabled' => $enabled]);
        $mfaService->clearCode($administrator);
        $auditLogger->record('administrator.mfa.updated', $administrator, $administrator, [
            'mfa_enabled' => $wasEnabled,
        ], [
            'mfa_enabled' => $enabled,
        ]);

        return back()->with('status', $enabled
            ? 'Email verification is now required when you sign in.'
            : 'Email verification has been disabled.');
    }
}
