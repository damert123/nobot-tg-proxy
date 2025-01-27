<?php

namespace App\Services;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopUpSendMessageService
{


    protected $settings;


    public function __construct()
    {
        $this->settings = (new AppInfo)
            ->setApiId(env('TELEGRAM_API_ID'))
            ->setApiHash(env('TELEGRAM_API_HASH'));
    }



    public function findSessionTelegram(int $telegramId): object
    {
        $sessionTelegram = DB::table('telegram_accounts')
            ->where('telegram_id', $telegramId)
            ->first();

        if (!$sessionTelegram) {
            Log::channel('top-up-messages')->error("No Telegram account found for ID: {$telegramId}");
            throw new \Exception("Invalid TelegramID: {$telegramId}", 400);
        }

        if ($sessionTelegram->status === 'Пауза') {
            Log::channel('top-up-messages')->info("Telegram session is on pause for account ID: {$sessionTelegram->id}");
            throw new \Exception('Телеграм сессия на паузе');
        }

        return $sessionTelegram;

    }

    public function initializeModelineProto(string $sessionPath): API
    {
        return new API($sessionPath);

    }

    public function sendMessageTopUp(API $madelineProto, string $message, int $to_id)
    {
        $history = $madelineProto->messages->getHistory([
            'peer' => 6673581915,
            'limit' => 10, // Сколько сообщений получить (ограничиваем последними 10 для экономии ресурсов)
        ]);

        $now = time();

//        Log::channel('planfix-messages')->info("СЕЙЧАС ВРЕМЯ {$now} ");
//        Log::channel('planfix-messages')->info("ПОСЛЕДНИЕ 10 СООБЩЕНИЙ:" .  json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//        foreach ($history['messages'] as $msg) {
//            // Если сообщение отправлено или получено за последние 10 минут, прерываем отправку
//            if (isset($msg['date']) && ($now - $msg['date']) <= 600) {
//                echo "Сообщение не отправлено: уже было общение за последние 10 минут.\n";
//                return;
//            }
//        }


        $madelineProto->messages->readHistory([
            'peer' => 6673581915,
        ]);

        $madelineProto->messages->sendMessage([
            'peer' => 6673581915,
            'message' => $message,
        ]);

    }





//    public function getUpdates()
//    {
//        return $this->madeline->getUpdates();
//    }
//
//    public function sendMessage($chatId, $message)
//    {
//        return $this->madeline->messages->sendMessage(['peer' => $chatId, 'message' => $message]);
//    }
}
