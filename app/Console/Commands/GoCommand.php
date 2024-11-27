<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
//        dd(Str::random(32));

        // Путь к сессии MadelineProto
//        $sessionFile = storage_path('telegram_sessions/user.madeline');

        $token = 'ml8FbPLWNpaBKvkUaLHYYG4v34WcWnqe';

        $planfix = DB::table('planfix_integrations')->where('token', $token)->first();

        $telegram  = DB::table('telegram_accounts')->where('id', $planfix->telegram_account_id)->first();

        $session = storage_path('telegram_sessions/79171275883.madeline');
        dd($session);



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
