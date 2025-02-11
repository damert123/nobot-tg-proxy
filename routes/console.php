<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


\Illuminate\Support\Facades\Schedule::command(\App\Console\Commands\QueueListen::class)
    ->everySecond()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command(\App\Console\Commands\TelegramListen::class)
    ->withoutOverlapping();
