<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestartMadelineProto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */


    public function handle()
    {
        // Получаем все аккаунты из таблицы
        $accounts = DB::table('telegram_accounts')->whereNotNull('session_path')->pluck('session_path');

        if ($accounts->isEmpty()) {
            $this->info('No accounts with session paths found.');
            return;
        }

        foreach ($accounts as $account) {
            try {
                $this->info("Processing account: $account");

                // Настройки MadelineProto
                $settings = new \danog\MadelineProto\Settings();
                $settings->setAppInfo(
                    (new AppInfo())
                        ->setApiId(env('TELEGRAM_API_ID'))
                        ->setApiHash(env('TELEGRAM_API_HASH'))
                );

                // Инициализация сессии

                $MadelineProto = new API($account, $settings);

//                // Остановка текущей сессии
//                $this->info('Stopping current session...');
//                $MadelineProto->stop();

                // Перезапуск сессии
                $this->info('Restarting session...');
                $MadelineProto->restart();

                $this->info("Session for account {$account} restarted successfully.");
            } catch (\Exception $e) {
                // Логирование ошибок для конкретного аккаунта
                $this->error("Error restarting session for account {$account}: " . $e->getMessage());
            }
        }
    }
}
