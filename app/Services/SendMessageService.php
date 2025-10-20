<?php

namespace App\Services;

use App\Models\TelegramAccount;
use App\Modules\ApiNoBot\ApiNobotService;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Peer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMessageService
{




    protected $settings;


    public function __construct()
    {
        $set = $this->settings = new Settings();

        $set->setAppInfo((new AppInfo)
            ->setApiId(env('TELEGRAM_API_ID'))
            ->setApiHash(env('TELEGRAM_API_HASH')));

        $set->setPeer((new Peer())
            ->setFullFetch(true));
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

//        if ($sessionTelegram->status === 'Пауза') {
//            Log::channel('top-up-messages')->info("Telegram session is on pause for account ID: {$sessionTelegram->id}");
//            throw new \Exception('Телеграм сессия на паузе');
//        }

        return $sessionTelegram;

    }

    public function initializeModelineProto(string $sessionPath): API
    {

        $madelineProto = new API($sessionPath, $this->settings);

        return $madelineProto;

    }

    /**
     * @throws \Exception
     */


    public function sendMessageDirectly(int $telegramId, string $message, string $telegramLink): string
    {
        try {
            Log::channel('top-up-messages')->error("НАЧИНАЕТСЯ ОТПРАВКА ЧРЕЗ telegram_link: {$telegramLink}");

            $crmService = new ApiNobotService();

            $parsedUsername = $crmService->extractUsernameFromLink($telegramLink);

            if (!$parsedUsername){
                Log::channel('top-up-messages')->error("Некорректная Telegram-ссылка '{$telegramLink}', сообщение не отправлено.");
                throw new \Exception("Некорректная Telegram-ссылка '{$telegramLink}', сообщение не отправлено.");
            }


            $mainSession = $this->findSessionTelegram($telegramId);
            $madelineProto = $this->initializeModelineProto($mainSession->session_path);

            $status = $this->attemptToSendMessage($madelineProto, $message, $parsedUsername);

            return $status;

        }catch (\Exception $e) {
            Log::channel('top-up-messages')->error("Ошибка на основном аккаунте ID: {$telegramId} - {$e->getMessage()}");

            return 'error';

        }
    }


    /**
     * @throws \Exception
     */

    private function attemptToSendMessage(API $madelineProto, string $message,  string $to_id): ?string
    {

        $now = time();

        Log::channel('top-up-messages')->info("СЕЙЧАС ВРЕМЯ {$now} ");


        $madelineProto->messages->readHistory([
            'peer' => $to_id,
        ]);

        $madelineProto->messages->sendMessage([
            'peer' => $to_id,
            'message' => $message,
        ]);

        Log::channel('top-up-messages')->info("Сообщение успешно отправлено с основного аккаунта");

        return 'sent';
    }
}
