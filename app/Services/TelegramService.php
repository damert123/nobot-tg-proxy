<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Settings\AppInfo;

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
        $sessionFile = storage_path('telegram_sessions/user.madeline');



        BasicEventHandler::startAndLoop($sessionFile, $this->settings);

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
