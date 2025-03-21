<?php

use App\Http\Controllers\PlanfixToTelegramController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/webhooks/planfix/chat', [PlanfixToTelegramController::class, 'handle']);
Route::post('/webhooks/telegram/message/topup', [TelegramController::class, 'handle']);
