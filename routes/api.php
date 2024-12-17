<?php

use App\Http\Controllers\PlanfixChatController;
use App\Http\Controllers\PlanfixToTelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::post('/webhooks/planfix/chat', [PlanfixChatController::class, 'handle']);
Route::post('/webhooks/planfix/chat', [PlanfixToTelegramController::class, 'handle']);
