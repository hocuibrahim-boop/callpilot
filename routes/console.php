<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hatirlatma bildirimleri - her dakika kontrol
Schedule::command('callpilot:reminders')->everyMinute()->withoutOverlapping();
