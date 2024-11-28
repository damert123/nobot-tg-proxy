<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Settings\AppInfo;
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
//        $sessionFile = storage_path('telegram_sessions/user.madeline');

        $sessions = [
            storage_path('telegram_sessions/79171275883.madeline'),
            storage_path('telegram_sessions/79178239146.madeline'),
        ];


        $MadelineProtos = [];
        foreach ($sessions as $session) {
            $api = new API($session);
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
