<?php

namespace App\Services;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use danog\MadelineProto\Settings\Logger as LoggerSettings;

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
            ->join('planfix_integrations', 'telegram_accounts.id', '=', 'planfix_integrations.telegram_account_id')
            ->whereNotNull('telegram_accounts.session_path')
            ->where('telegram_accounts.status', 'Активен')
            ->pluck('telegram_accounts.session_path');

        if ($sessions->isEmpty()) {
            return;
        }

        $MadelineProtos = [];
        foreach ($sessions as $sessionPath) {

            $api = new API($sessionPath);

            $api->start();

            $MadelineProtos[] = $api;
        }

        try {
            API::startAndLoopMulti($MadelineProtos, BasicEventHandler::class);
        } catch (\Throwable $e) {
            Log::error("Мульти-цикл упал: ".$e->getMessage());

        }

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
