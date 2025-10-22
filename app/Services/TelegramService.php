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

    public function listenForMessage(string $sessionPath)
    {

        $session = DB::table('telegram_accounts')
            ->join('planfix_integrations', 'telegram_accounts.id', '=', 'planfix_integrations.telegram_account_id')
            ->whereNotNull('telegram_accounts.session_path')
            ->where('telegram_accounts.status', 'Активен')
            ->where('telegram_accounts.session_path', $sessionPath)
            ->value('telegram_accounts.session_path');

        if ($session->isEmpty()) {
            return;
        }

        BasicEventHandler::startAndLoop($session);

    }
}
