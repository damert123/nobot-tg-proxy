<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'go';

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
        dd(Str::random(32));

//        // Путь к сессии MadelineProto
//        $sessionFile = storage_path('telegram_sessions/user.madeline');
//
//        // Создаем экземпляр MadelineProto
//        $settings = (new \danog\MadelineProto\Settings\AppInfo)
//            ->setApiId(env('TELEGRAM_API_ID'))
//            ->setApiHash(env('TELEGRAM_API_HASH'));
//
//        $madeline = new API($sessionFile, $settings);
//
//
//        try {
//
//
////            $sendMessage = $madeline->messages->sendMessage([
////                'peer' => 1813333457,
////                'message' => "Спасибо за ваше сообщение! Мы скоро с вами свяжемся."
////            ]);
//
//            // Записываем данные в лог
//            Log::channel('tg-messages')->info("Написал ");
//
//            $this->info("Информация о пользователе записана в лог.");
//        } catch (\Throwable $e) {
//            $this->error("Ошибка при получении информации о пользователе: {$e->getMessage()}");
//        }
//    }
    }


}
