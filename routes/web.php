<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => 'CallPilot', 'status' => 'ok', 'version' => '1.0']);
});
