<?php

namespace App\Listeners;

use App\Events\TelegramAccountCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateSupervisorConfigForTelegram
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TelegramAccountCreated $event): void
    {
        Log::info("123123123");
        $account = $event->telegramAccount;
        $phone = $account->getPhone();
        Log::info("Телефон {$phone}");


        $sessionPath = storage_path("telegram_sessions/{$phone}.madeline");
        $logPath = storage_path("logs/tg_session_{$phone}.log");
        $confPath = "/etc/supervisor/conf.d/tg_session_{$phone}.conf";

        Log::info("Preparing supervisor config for phone: {$phone}");

        $config = "[program:tg_session_{$phone}]\n" .
            "process_name=%(program_name)s\n" .
            "command=php /home/developer/nobot-tg-proxy/artisan telegram:listen --id={$account->getId()}\n" .
            "directory=/home/developer/nobot-tg-proxy\n" .
            "autostart=true\n" .
            "autorestart=true\n" .
            "stopasgroup=true\n" .
            "killasgroup=true\n" .
            "user=developer\n" .
            "redirect_stderr=true\n" .
            "stdout_logfile={$logPath}\n" .
            "stdout_logfile_maxbytes=10MB\n" .
            "stdout_logfile_backups=2";

        File::put($confPath, $config);
        Log::info("Supervisor config written to {$confPath}");

        exec('sudo supervisorctl reread && sudo supervisorctl update');
        Log::info("Supervisor reread and update executed");


    }
}
