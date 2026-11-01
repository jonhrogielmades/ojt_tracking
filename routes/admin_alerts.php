<?php

use App\Http\Controllers\Admin\AdminAlertController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function (): void {
        Route::get('/alerts', [AdminAlertController::class, 'index'])->name('alerts.index');
        Route::patch('/alerts/read-all', [AdminAlertController::class, 'readAll'])->name('alerts.read-all');
        Route::patch('/alerts/{notification}/read', [AdminAlertController::class, 'read'])->name('alerts.read');
    });
