<?php

use App\Http\Controllers\PlanfixChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/planfix/chat', [PlanfixChatController::class, 'handle']);
