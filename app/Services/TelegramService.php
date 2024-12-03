<?php

namespace App\Services;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramService
{


    protected $settings;


    public function __construct()
    {
        $this->settings = (new AppInfo)
            ->setApiId(env('TELEGRAM_API_ID'))
            ->setApiHash(env('TELEGRAM_API_HASH'));
    }

    public function listenForMessage()
    {

        $sessions = DB::table('telegram_accounts')
            ->whereNotNull('session_path')
            ->where('status', 'Активен')
            ->pluck('session_path');

        if ($sessions->isEmpty()) {
            return; // Можно добавить логирование или сообщение
        }

        $MadelineProtos = [];
        foreach ($sessions as $sessionPath) {

            $api = new API($sessionPath);
            $api->start();
            $MadelineProtos[] = $api;
        }

//        BasicEventHandler::startAndLoop($sessionFile, $this->settings);
        API::startAndLoopMulti($MadelineProtos, BasicEventHandler::class);

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
