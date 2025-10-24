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


        $tmpConfPath = storage_path("app/tmp/tg_session_{$phone}.conf");
        $finalConfPath = "/etc/supervisor/conf.d/tg_session_{$phone}.conf";

        Log::info("Preparing supervisor config for phone: {$phone}");
        Log::info("Conf path: {$finalConfPath}");

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


        $tmpDir = dirname($tmpConfPath);
        if (!File::exists($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
            Log::info("Created temporary directory: {$tmpDir}");
        }

        File::put($tmpConfPath, $config);
        Log::info("Supervisor config written to temporary location: {$tmpConfPath}");


        if (!File::exists($tmpConfPath)){
            Log::error('Temporary config file was not created!');
            return;
        }

        $moveCommand = "sudo mv {$tmpConfPath} {$finalConfPath}";

        exec($moveCommand, $moveOutput, $moveReturnCode);

        Log::info("Move command return code: {$moveReturnCode}");
        Log::info("Move command output: " . implode("\n", $moveOutput));


        if ($moveReturnCode === 0){
            Log::info("✅ Config successfully moved to supervisor directory");

            exec("sudo chmod 644 {$finalConfPath}", $chmodOutput, $chmodReturnCode);

            Log::info("Chmod return code: {$chmodReturnCode}");

            Log::info("Updating supervisor...");
            exec('sudo supervisorctl reread', $rereadOutput, $rereadCode);
            exec('sudo supervisorctl update', $updateOutput, $updateCode);

            Log::info("Supervisor reread return code: {$rereadCode}");
            Log::info("Supervisor update return code: {$updateCode}");
            Log::info("Supervisor reread output: " . implode("\n", $rereadOutput));
            Log::info("Supervisor update output: " . implode("\n", $updateOutput));

            Log::info("✅ Supervisor config successfully created and loaded");



        }else{
            Log::error("❌ Failed to move config file to supervisor directory");
            Log::error("Move command failed with code: {$moveReturnCode}");
        }
    }
}
