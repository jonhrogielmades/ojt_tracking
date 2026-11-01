<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\ArchivedStudentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\MfaChallengeController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\MfaSettingsController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Middleware\EnsureUserCapability;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind(
    'administrator',
    fn (string $value): User => User::query()->administrators()->findOrFail($value),
);

Route::middleware('guest')->group(function (): void {
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');

    Route::get('/admin/mfa/challenge', [MfaChallengeController::class, 'create'])->name('admin.mfa.challenge');
    Route::post('/admin/mfa/challenge', [MfaChallengeController::class, 'store'])
        ->middleware('throttle:admin-mfa')
        ->name('admin.mfa.verify');
    Route::post('/admin/mfa/resend', [MfaChallengeController::class, 'resend'])
        ->middleware('throttle:admin-mfa')
        ->name('admin.mfa.resend');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function (): void {
        Route::get('/security/mfa', [MfaSettingsController::class, 'edit'])->name('mfa.edit');
        Route::put('/security/mfa', [MfaSettingsController::class, 'update'])->name('mfa.update');

        Route::patch('/students/{studentId}/restore', [ArchivedStudentController::class, 'restore'])
            ->name('students.restore');
        Route::delete('/students/{studentId}/purge', [ArchivedStudentController::class, 'destroy'])
            ->name('students.purge');

        Route::resource('administrators', AdministratorController::class)
            ->except(['show', 'destroy'])
            ->middleware(EnsureUserCapability::class.':administrators.manage');

        Route::get('/audit-logs', AuditLogController::class)
            ->middleware(EnsureUserCapability::class.':audit.view')
            ->name('audit.index');

        Route::get('/backups', [BackupController::class, 'index'])
            ->middleware(EnsureUserCapability::class.':backups.manage')
            ->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])
            ->middleware(EnsureUserCapability::class.':backups.manage')
            ->name('backups.store');
        Route::post('/backups/{backup}/verify', [BackupController::class, 'verify'])
            ->middleware(EnsureUserCapability::class.':backups.manage')
            ->name('backups.verify');
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])
            ->middleware(EnsureUserCapability::class.':backups.manage')
            ->name('backups.destroy');

        Route::get('/system-health', SystemHealthController::class)
            ->middleware(EnsureUserCapability::class.':system-health.view')
            ->name('system-health');
    });
