<?php

namespace App\Console\Commands;

use App\Modules\TelegramAccount\TelegramAccountEntity;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-message';

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

        try {
            $appInfo  = (new AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            $madelineProto = new API($sessionPathTestAccount, $appInfo);

            $madelineProto->sendMessage(406210384, 'ТЕСТ КОМАНДЫ СООБЩЕНИЯ');

        }catch (\Throwable $throwable){
            $this->error("Ошибка при восстановлении: {$throwable->getMessage()}");
            Log::channel('planfix-messages')->error("TEST MESSAGE error", [
                'exception' => $throwable->getMessage(),
            ]);
            return 1;
        }


        $this->info("СООБЩЕНИЕ ОТПРАВЛЕНО УСПЕШНО успешно.");
        return 0;
    }
}
