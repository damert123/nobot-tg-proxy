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

    public function listenForMessage(int $accountId)
    {

        $sessionPath = DB::table('telegram_accounts')
            ->whereNotNull('telegram_accounts.session_path')
            ->where('telegram_accounts.status', 'Активен')
            ->where('telegram_accounts.id', $accountId)
            ->value('telegram_accounts.session_path');

        if (empty($sessionPath)) {
            echo "Сессия не найдена для аккаунта ID: {$accountId}\n";
            return;
        }

        BasicEventHandler::startAndLoop($sessionPath);

    }
}
