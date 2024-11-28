<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramAccountController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(AdminMiddleware::class)->group(function (){
});
Route::get('/dashboard', [AdminController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/telegram/add', [TelegramAccountController::class, 'create'])->name('telegram.add');
Route::post('/telegram/add', [TelegramAccountController::class, 'store'])->name('telegram.store');
Route::get('/telegram/code', [TelegramAccountController::class, 'showCodeForm'])->name('telegram.code');
Route::post('/telegram/code', [TelegramAccountController::class, 'verifyCode'])->name('telegram.verify');

Route::get('/telegram/accounts', [TelegramAccountController::class, 'index'])->name('telegram.index');

Route::delete('/telegram/accounts/{id}', [TelegramAccountController::class, 'destroy'])->name('telegram.destroy');


Route::middleware('auth')->group(function () {




    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
