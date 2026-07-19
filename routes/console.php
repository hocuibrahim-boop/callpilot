<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hatirlatma bildirimleri - her dakika kontrol edilir.
// withoutOverlapping() cache kilidi gerektirdigi icin kullanilmiyor;
// komut zaten saniyeler icinde bitiyor ve mukerrer gonderim
// bildirim_gonderildi bayragiyla engelleniyor.
Schedule::command('callpilot:reminders')->everyMinute();
