<?php

namespace App\Console\Commands;

use App\Modules\TelegramAccount\TelegramAccountEntity;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Peer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreDialogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-dialogs';

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
        $sessionPathTestAccount = TelegramAccountEntity::findTestSession()->getSessionPath();

        if (!$sessionPathTestAccount){
            $this->error('Тестовый аккаунт не найден');

            return 1;
        }

        $this->info("Инициализируем MadelineProto, session_path={$sessionPathTestAccount}");

        try {

            $peerSetings = (new Peer())
                ->setCacheAllPeersOnStartup(true);



            $appInfo  = (new AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            $settings = (new Settings())
                ->setPeer($peerSetings)
                ->setAppInfo($appInfo);


            $madelineProto = new API($sessionPathTestAccount, $settings);

            $madelineProto->start();

        } catch (\Throwable $e){
            $this->error("Ошибка при восстановлении: {$e->getMessage()}");
            Log::channel('planfix-messages')->error("RestoreDialogs error", [
                'exception' => $e->getMessage(),
            ]);
            return 1;
        }

        $this->info("Команда выполнена успешно.");
        return 0;
    }
}
