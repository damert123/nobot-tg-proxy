<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PlanfixController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramAccountController;
use App\Http\Controllers\TelegramApiEnv;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [AdminController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(AdminMiddleware::class)->group(function (){

    Route::get('/telegram/add', [TelegramAccountController::class, 'create'])->name('telegram.add');
    Route::post('/telegram/add', [TelegramAccountController::class, 'store'])->name('telegram.store');
    Route::get('/telegram/code', [TelegramAccountController::class, 'showCodeForm'])->name('telegram.code');
    Route::post('/telegram/code', [TelegramAccountController::class, 'verifyCode'])->name('telegram.verify');
    Route::post('/telegram/recode/{phone}', [TelegramAccountController::class, 'resendCode'])->name('telegram.recode');
    Route::get('/telegram/verify-two-factor', [TelegramAccountController::class, 'showTwoFactorCode'])->name('telegram.twofactor');
    Route::post('/telegram/verify-two-factor', [TelegramAccountController::class, 'verifyTwoFactorCode'])->name('telegram.verify.twofactor');
    Route::get('/telegram/accounts', [TelegramAccountController::class, 'index'])->name('telegram.index');
    Route::delete('/telegram/accounts/{id}', [TelegramAccountController::class, 'destroy'])->name('telegram.destroy');
    Route::post('/telegram/accounts/stop/{id}', [TelegramAccountController::class, 'stop'])->name('telegram.stop');
    Route::post('/telegram/accounts/start/{id}', [TelegramAccountController::class, 'start'])->name('telegram.start');


    Route::get('/planfix/add', [PlanfixController::class, 'create'])->name('planfix.add');
    Route::post('/planfix/add', [PlanfixController::class, 'store'])->name('planfix.store');
    Route::get('/planfix/chats', [PlanfixController::class, 'index'])->name('planfix.index');
    Route::delete('/planfix/chats/{id}', [PlanfixController::class, 'destroy'])->name('planfix.destroy');


    Route::get('/config/telegram', [TelegramApiEnv::class, 'create'])->name('telegram.api.add');
    Route::post('/config/telegram', [TelegramApiEnv::class, 'store'])->name('telegram.api.store');
    Route::post('/config/telegram/restart', [TelegramApiEnv::class, 'restart'])->name('telegram.api.restart');


    Route::get('/users', [UserController::class, 'index'])->name('user.index');
    Route::put('/users/{id}/role', [UserController::class, 'updateRole'])->name('users.updateRole');


    Route::post('/account/handle/restart', TelegramAccountController::class, 'restartHandler')->name('account.restart');


});

Route::middleware('auth')->group(function () {




    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
