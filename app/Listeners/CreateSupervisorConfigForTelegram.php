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
        $account = $event->telegramAccount;
        $phone = $account->getPhone();

        $sessionPath = storage_path("telegram_sessions/{$phone}.madeline");
        $logPath = storage_path("logs/tg_session_{$phone}.log");
        $confPath = "/etc/supervisor/conf.d/tg_session_{$phone}.conf";

        Log::info("Preparing supervisor config for phone: {$phone}");

        $config = <<<CONF
[program:tg_session_{$phone}]
process_name=%(program_name)s
command=php /home/developer/nobot-tg-proxy/artisan telegram:listen --id={$account->getId()}
directory=/home/developer/nobot-tg-proxy
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=developer
redirect_stderr=true
stdout_logfile={$logPath}
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=2
CONF;

        File::put($confPath, $config);
        Log::info("Supervisor config written to {$confPath}");

        exec('sudo supervisorctl reread && sudo supervisorctl update');
        Log::info("Supervisor reread and update executed");


    }
}
