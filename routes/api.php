<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PanelController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/auth/login', [AuthController::class, 'login']);

// Verimor Webhook (secret ile korunuyor)
Route::post('/webhook/verimor', [WebhookController::class, 'verimor'])
    ->withoutMiddleware(\App\Http\Middleware\ApiTokenAuth::class);

// Authenticated routes
Route::middleware(\App\Http\Middleware\ApiTokenAuth::class)->group(function () {

    Route::get('/auth/me', [AuthController::class, 'me']);

    // Aramalar
    Route::get('/calls', [CallController::class, 'index']);
    Route::post('/calls', [CallController::class, 'store']);
    Route::post('/calls/{call}/sahsi', [CallController::class, 'sahsiIsaretle']);
    Route::post('/calls/{call}/geri-arandi', [CallController::class, 'geriArandi']);

    // Müşteriler
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    Route::patch('/leads/{lead}/asama', [LeadController::class, 'updateAsama']);
    Route::post('/leads/{lead}/notlar', [LeadController::class, 'addNote']);
    Route::post('/leads/{lead}/whatsapp', [LeadController::class, 'whatsapp']);

    // Hatırlatmalar
    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::patch('/reminders/{reminder}', [ReminderController::class, 'update']);

    // Yönetici paneli
    Route::get('/panel/ozet', [PanelController::class, 'ozet']);
    Route::get('/panel/danismanlar', [PanelController::class, 'danismanlar']);
    Route::get('/panel/huni', [PanelController::class, 'huni']);
    Route::get('/panel/kacan', [PanelController::class, 'kacan']);
});
