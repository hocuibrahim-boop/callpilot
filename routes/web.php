<?php

use Illuminate\Support\Facades\Route;

// Tanitim sitesi
Route::get('/', function () {
    return response()->file(public_path('site/index.html'));
});

// Etkilesimli demo
Route::get('/demo', function () {
    return response()->file(public_path('site/demo.html'));
});

// Uygulama indirme
Route::get('/indir/CallPilot.apk', function () {
    $yol = public_path('indir/CallPilot.apk');
    abort_unless(file_exists($yol), 404);
    return response()->download($yol, 'CallPilot.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
});

// Servis durumu (eski ana sayfa yaniti)
Route::get('/durum', function () {
    return response()->json(['app' => 'CallPilot', 'status' => 'ok', 'version' => '1.0']);
});
