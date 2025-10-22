<?php

namespace App\Providers;

use App\Events\TelegramAccountCreated;
use App\Listeners\CreateSupervisorConfigForTelegram;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            TelegramAccountCreated::class,
            [CreateSupervisorConfigForTelegram::class, 'handle']
        );

        User::observe(UserObserver::class);
    }
}
