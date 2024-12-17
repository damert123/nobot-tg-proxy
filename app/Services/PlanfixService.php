<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanfixService
{
    public function initializeModelineProto(string $sessionPath): API
    {
        return new API($sessionPath);
    }

    public function validateWebhookData(array $data): void
    {
        if (!isset($data['chatId']) || (empty($data['message']) && empty($data['attachments']))) {
            Log::channel('planfix-messages')->error('Invalid Planfix webhook data: Missing required fields.');
            throw new \Exception('Missing required fields.', 400);
        }

    }

    public function getIntegrationAndAccount(string $token): object
    {
        $integration = DB::table('planfix_integrations')
            ->where('token', $token)
            ->first();

        if (!$integration) {
            Log::channel('planfix-messages')->error("No Telegram account found for token: {$token}");
            throw new \Exception("Invalid token: {$token}", 400);
        }

        $telegramAccount = DB::table('telegram_accounts')
            ->where('id', $integration->telegram_account_id)
            ->first();

        if (!$telegramAccount){
            Log::channel('planfix-messages')->error("No Telegram account found for ID: {$integration->telegram_account_id}");
            throw new \Exception("Не найден телеграм аккаут для этого токена: {$token}", 400);
        }

        if ($telegramAccount->status === 'Пауза') {
            Log::channel('planfix-messages')->info("Telegram session is on pause for account ID: {$telegramAccount->id}");
            throw new \Exception('Телеграм сессия на паузе');

        }

        return $telegramAccount;

    }

    public function sendMessage(API $madelineProto, string $chatId, string $message): void
    {
        $madelineProto->messages->readHistory([
            'peer' => $chatId,
        ]);

        $messageLength = mb_strlen($message);
        $typingDuration = $this->calculateTypingDuration($messageLength);

        $this->simulateTyping($madelineProto, $chatId, $typingDuration, 'sendMessageTypingAction');

        $resultMessage = $madelineProto->messages->sendMessage([
            'peer' => $chatId,
            'message' => $message,
            'entities' => [
                [
                    '_' => 'messageEntityTextUrl',
                    'offset' => strlen($message),
                    'length' => 1,
                    'url' => 'planfix://internal' // Невидимая метка
                ]
            ],
        ]);

        $idMessageMedia = $resultMessage['id'];

        DB::table('id_message_to_tg_telegram')->insert([
            'message_id' => $idMessageMedia
        ]);

        Log::channel('planfix-messages')->info("СООБЩЕНИЕ из CRM отправлено в чат {$chatId}: {$message}");

    }

    public function sendAttachment(API $madelineProto, string $chatId, array $attachment, ?string $message): void
    {

        $madelineProto->messages->readHistory([
            'peer' => $chatId,
        ]);

        sleep(5);
        try {
            $fileUrl = $attachment['url'];
            $fileName = $attachment['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));


            $mediaType = $this->getMediaTypeByExtension($fileExtension);


            if ($mediaType === 'inputMediaUploadedDocument' && $fileExtension === 'ogg') {
                $duration = $this->estimateDurationBySize($fileUrl);
                $this->simulateTyping($madelineProto, $chatId, $duration, 'sendMessageRecordAudioAction');
            }


            $result = $madelineProto->messages->sendMedia([
                'peer' => $chatId,
                'media' => [
                    '_' => $mediaType,
                    'file' => $fileUrl,
                ],
                'message' => $message ?? '',
            ]);


            $mediaId = $result['updates'][1]['message']['media']['document']['id']
                ?? $result['updates'][1]['message']['media']['photo']['id']
                ?? null;

            if ($mediaId) {
                DB::table('id_message_to_tg_telegram')->insert([
                    'message_id' => $mediaId
                ]);
            }

            Log::channel('planfix-messages')->info("Вложение из CRM отправлено в чат {$chatId}: {$fileName}");

        } catch (\Throwable $e) {
            Log::channel('planfix-messages')->error("Ошибка при отправке вложения в Telegram: {$e->getMessage()}");
            throw $e;
        }

    }

    public function getMediaTypeByExtension(string $extension): string
    {
        return match ($extension) {
            'png', 'jpg', 'jpeg' => 'inputMediaUploadedPhoto',
            'mp4', 'mkv', 'mov', 'avi', 'ogg' => 'inputMediaUploadedDocument',
            default => throw new \Exception("Unsupported file extension: {$extension}"),
        };

    }

    private function estimateDurationBySize(string $fileUrl): int
    {

        $headers = get_headers($fileUrl, 1);
        $fileSize = $headers['Content-Length'] ?? 0;

        $fileSizeKB = $fileSize / 1024;

        return intval(($fileSizeKB / 40) * 10);
    }

    private function simulateTyping(API $madelineProto, string $chatId, int $duration, string $actionType): void
    {

        $startTime = time();
        while (time() - $startTime < $duration) {
            $madelineProto->messages->setTyping([
                'peer' => $chatId,
                'action' => [
                    '_' => $actionType,
                ],
            ]);

        }
    }

    private function calculateTypingDuration(int $messageLength): int
    {
        return match (true) {
            $messageLength < 20 => 3,
            $messageLength < 100 => 8,
            $messageLength < 300 => 15,
            $messageLength < 500 => 20,
            default => 30,
        };
    }



}
