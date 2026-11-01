<?php

use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\StudentCalendarController;
use App\Http\Controllers\StudentNotificationController;
use App\Http\Controllers\StudentPortfolioController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\WeeklySubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:student'])->group(function (): void {
    Route::get('/submissions', [WeeklySubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [WeeklySubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [WeeklySubmissionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('submissions.store');
    Route::get('/submissions/{submission}', [WeeklySubmissionController::class, 'show'])->name('submissions.show');

    Route::get('/corrections', [CorrectionRequestController::class, 'index'])->name('corrections.index');
    Route::post('/corrections/{dtr}', [CorrectionRequestController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('corrections.store');

    Route::get('/calendar', StudentCalendarController::class)->name('student.calendar');
    Route::get('/progress', [StudentProgressController::class, 'index'])->name('student.progress');
    Route::patch('/progress/target', [StudentProgressController::class, 'update'])->name('student.progress.update');
    Route::get('/portfolio', [StudentPortfolioController::class, 'index'])->name('student.portfolio');
    Route::get('/portfolio/print', [StudentPortfolioController::class, 'printable'])->name('student.portfolio.print');

    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('student.notifications');
    Route::patch('/notifications/read-all', [StudentNotificationController::class, 'readAll'])->name('student.notifications.read-all');
    Route::patch('/notifications/{notification}/read', [StudentNotificationController::class, 'read'])->name('student.notifications.read');
});
