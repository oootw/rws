<?php

use App\Jobs\SendSubscriptionReminder;
use App\Jobs\SendWeeklyDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendWeeklyDigest)
    ->weeklyOn(1, '9:00')
    ->timezone('Europe/Moscow');

Schedule::job(new SendSubscriptionReminder)
    ->dailyAt('9:00')
    ->timezone('Europe/Moscow');
