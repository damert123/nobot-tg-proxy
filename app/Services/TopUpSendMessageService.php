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

class TopUpSendMessageService
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

        if ($sessionTelegram->status === 'Пауза') {
            Log::channel('top-up-messages')->info("Telegram session is on pause for account ID: {$sessionTelegram->id}");
            throw new \Exception('Телеграм сессия на паузе');
        }

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

    public function sendMessageTopUpDirectly(int $telegramId, string $message, string $telegramLink): void
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

            $this->attemptToSendMessage($madelineProto, $message, $parsedUsername);

        }catch (\Exception $e) {
            Log::channel('top-up-messages')->error("Ошибка на основном аккаунте ID: {$telegramId} - {$e->getMessage()}");

            try {
                $crmService = new ApiNobotService();
                $parsedUsername = $crmService->extractUsernameFromLink($telegramLink);

                $this->tryAlternativeAccounts($message, $parsedUsername, $telegramId);
            }catch (\Exception $e){
                Log::channel('top-up-messages')->error("Ошибка при использовании альтернативных аккаунтов: {$e->getMessage()}");
                throw $e;
            }
        }
    }


    /**
     * @throws \Exception
     */
    public function sendMessageTopUpTask(int $telegramId, string $message, int $taskId): void
    {
        Log::channel('top-up-messages')->error("НАЧИНАЕТСЯ ОТПРАВКА ЧРЕЗ task: {$taskId}");

        $crmService = new ApiNobotService();

        $task = $crmService->getTask($taskId);


        if (!$task || !isset($task['task']['assigner']['id'])) {
            Log::channel('top-up-messages')->error("Не найден assigner для задачи ID: {$taskId}");
            throw new \Exception("Не найден assigner для задачи ID: {$taskId}");

        }

        $contactId = $task['task']['assigner']['id'];
        $contact = $crmService->getContact($contactId);
        $telegramLink = $contact['contact']['telegram'] ?? null;


        if (!$telegramLink) {
            Log::channel('top-up-messages')->warning("У контакта {$contactId} нет Telegram-ссылки, сообщение не отправлено.");
            throw new \Exception("У контакта {$contactId} нет Telegram-ссылки, сообщение не отправлено.");

        }

        $parsedUsername = $crmService->extractUsernameFromLink($telegramLink);
        if (!$parsedUsername) {
            Log::channel('top-up-messages')->warning("Некорректная Telegram-ссылка '{$telegramLink}', сообщение не отправлено.");
            throw new \Exception("Некорректная Telegram-ссылка '{$telegramLink}', сообщение не отправлено.");
        }

        $to_id = $parsedUsername;

        Log::channel('top-up-messages')->info("ТЕСТОВЫЙ ЛИНК ТЕЛЕГРАМ:" . $to_id);


        try {
            $mainSession = $this->findSessionTelegram($telegramId);
            $madelineProto = $this->initializeModelineProto($mainSession->session_path);

            $this->attemptToSendMessage($madelineProto, $message, $to_id);
        }catch (\Exception $e) {
            Log::channel('top-up-messages')->error("Ошибка на основном аккаунте ID: {$telegramId} - {$e->getMessage()}");

            try {
                $this->tryAlternativeAccounts($message, $to_id, $telegramId);
            }catch (\Exception $e){
                Log::channel('top-up-messages')->error("Ошибка при использовании альтернативных аккаунтов: {$e->getMessage()}");
                throw $e;
            }
        }

    }

    private function attemptToSendMessage(API $madelineProto, string $message,  string $to_id): void
    {


        $history = $madelineProto->messages->getHistory([
            'peer' => $to_id,
            'limit' => 1, // Сколько сообщений получить (ограничиваем последними 10 для экономии ресурсов)
        ]);

        $now = time();

        Log::channel('top-up-messages')->info("СЕЙЧАС ВРЕМЯ {$now} ");
        Log::channel('top-up-messages')->info("ПОСЛЕДНИЕ 1 Сообщение:" .  json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        foreach ($history['messages'] as $msg) {
            // Если сообщение отправлено или получено за последние 10 минут, прерываем отправку
            if (isset($msg['date']) && ($now - $msg['date']) <= 300) {
                Log::channel('top-up-messages')->info("Сообщение не отправлено: уже было общение за последние 5 минут.");
                echo "Сообщение не отправлено: уже было общение за последние 5 минут.\n";
                return;
            }
        }


        $madelineProto->messages->readHistory([
            'peer' => $to_id,
        ]);

        $madelineProto->messages->sendMessage([
            'peer' => $to_id,
            'message' => $message,
        ]);

        Log::channel('top-up-messages')->info("Сообщение успешно отправлено с основного аккаунта");


    }

    private function tryAlternativeAccounts(string $message, string $to_id, int $excludedTelegramId): void
    {
        $accounts = DB::table('telegram_accounts')
            ->where('status', 'Активен')
            ->whereNotNull('session_path')
            ->where('telegram_id', '!=', $excludedTelegramId) // Исключаем основной аккаунт
            ->inRandomOrder()
            ->get();

        foreach ($accounts as $account){
            try {
                $madelineProto = $this->initializeModelineProto($account);
                $this->attemptToSendMessage($madelineProto, $message, $to_id);

                Log::channel('planfix-messages')->info("Сообщение успешно отправлено с альтернативного аккаунта ID: {$account->id}");
                return;
            } catch (\Exception $e){
                Log::channel('top-up-messages')->error("Ошибка на аккаунте ID: {$account->id} - {$e->getMessage()}");
                continue;
            }
        }

        throw new \Exception('Не удалось отправить сообщение ни с одного доступного аккаунта');


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
