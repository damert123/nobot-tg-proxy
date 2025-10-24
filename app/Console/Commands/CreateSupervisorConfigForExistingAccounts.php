<?php

namespace App\Console\Commands;

use App\Modules\TelegramAccount\TelegramAccountEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateSupervisorConfigForExistingAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:create-supervisor-configs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать supervisor конфиги для всех существующих активных аккаунтов';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Поиск аккаунтов Telegram...');
        /** @var Collection<int, TelegramAccountEntity> $accounts */
        $accounts = TelegramAccountEntity::getAllAccounts();

        $this->info("Активных аккаунтов в БД: " . $accounts->count());

        $createdCount = 0;
        $errorCount = 0;

        foreach ($accounts as $account){
            try {
                $this->createSupervisorConfig($account);
                $createdCount++;
                $this->info("✅ Создан конфиг для: {$account->getPhone()}");
            }catch (\Exception $e){
                $errorCount++;
                $this->error("❌ Ошибка для {$account->getPhone()}: " . $e->getMessage());
            }

        }

        $this->info("\nРезультат:");
        $this->info("Успешно создано: {$createdCount}");
        $this->info("Ошибок: {$errorCount}");

        if ($createdCount > 0){
            $this->info("\nОбновляем supervisor...");
            exec('sudo supervisorctl reread && sudo supervisorctl update', $output, $returnCode);

            if ($returnCode === 0){
                $this->info("✅ Supervisor успешно обновлен");
            }else{
                $this->error("❌ Ошибка обновления supervisor");
            }
        }

        return 0;

    }

    private function createSupervisorConfig(TelegramAccountEntity $account): void
    {
        $phone = $account->getPhone();
        $logPath = storage_path("logs/tg_session_{$phone}.log");
        $tmpConfPath = storage_path("app/tmp/tg_session_{$phone}.conf");
        $finalConfPath = "/etc/supervisor/conf.d/tg_session_{$phone}.conf";

        if (File::exists($finalConfPath)){
            $this->warn("Конфиг уже существует для аккаунта {$account->getPhone()}");
            return;
        }

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
        }

        File::put($tmpConfPath, $config);

        $moveCommand = "sudo mv {$tmpConfPath} {$finalConfPath}";

        exec($moveCommand, $moveOutput, $moveReturnCode);

        if ($moveReturnCode !== 0){
            throw new \Exception("Не удалось переместить конфиг: " . implode("\n", $moveOutput));
        }


    }
}
