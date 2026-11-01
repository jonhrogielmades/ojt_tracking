<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('students:send-reminders')
    ->dailyAt('18:00')
    ->withoutOverlapping();

Schedule::command('backup:create --prune')
    ->dailyAt('02:00')
    ->withoutOverlapping();
