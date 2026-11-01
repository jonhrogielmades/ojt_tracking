<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentDocumentController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()?->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
})->name('home');

Route::bind(
    'student',
    fn (string $value): User => User::query()->students()->findOrFail($value),
);

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('admin.login.store');
});

Route::middleware(['auth', 'active', 'role:student'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/install', 'pwa.install')->name('install.help');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/dtr/{dtr}/history', [DtrController::class, 'history'])->name('dtr.history');
    Route::resource('dtr', DtrController::class)->except(['show']);

    Route::get('/activities/{activity}/attachment', [ActivityLogController::class, 'downloadAttachment'])
        ->name('activities.attachment');
    Route::resource('activities', ActivityLogController::class)->except(['show']);

    Route::get('/documents', [StudentDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [StudentDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [StudentDocumentController::class, 'download'])
        ->name('documents.download');
    Route::delete('/documents/{document}', [StudentDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/monthly/print', [ReportController::class, 'monthlyPrint'])->name('reports.monthly.print');
    Route::get('/reports/weekly', [ReportController::class, 'weekly'])->name('reports.weekly');
    Route::get('/reports/weekly/print', [ReportController::class, 'weeklyPrint'])->name('reports.weekly.print');
    Route::get('/reports/dtr.csv', [ReportController::class, 'dtrCsv'])->name('reports.dtr_csv');

    Route::post('/sync/dtr', [OfflineSyncController::class, 'storeDtr'])
        ->middleware('throttle:sync')
        ->name('sync.dtr');
    Route::post('/sync/activities', [OfflineSyncController::class, 'storeActivity'])
        ->middleware('throttle:sync')
        ->name('sync.activities');

});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('students', AdminStudentController::class);
    });

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/student_features.php';
require __DIR__.'/security_platform.php';
require __DIR__.'/admin_operations.php';
require __DIR__.'/admin_alerts.php';
