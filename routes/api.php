<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TildaWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Вебхук для Тильды (POST запрос)
Route::post('/webhooks/tilda', [TildaWebhookController::class, 'handle']);