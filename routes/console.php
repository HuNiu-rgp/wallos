<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:notify')
    ->dailyAt('12:00')
    ->timezone(SystemSetting::value('timezone') ?: config('app.timezone'))
    ->withoutOverlapping();
