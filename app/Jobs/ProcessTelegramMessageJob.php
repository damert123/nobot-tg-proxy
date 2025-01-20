<?php

namespace App\Jobs;

use App\Models\MessagePlanfix;
use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Redis\XADD;
use Predis\Command\Redis\XREAD;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;


    protected $streamKey = 'telegram_stream';

    public function handle()
    {
        $redis = app('redis');
        $group = 'telegram_group';
        $consumer = 'consumer_' . uniqid();

        // Убедимся, что группа существует
        Redis::command('XGROUP', ['CREATE', $this->streamKey, $group, '0', 'MKSTREAM']);

        while (true) {
            $messages = Redis::command('XREADGROUP', [$group, $consumer, 'STREAMS', $this->streamKey, '>']);

            if (!empty($messages[$this->streamKey])) {
                foreach ($messages[$this->streamKey] as $id => $message) {
                    try {
                        $this->processMessage($message);

                        // Подтверждаем обработку сообщения
                        Redis::command('XACK', [$this->streamKey, $group, $id]);
                    } catch (\Exception $e) {
                        Log::error("Ошибка обработки сообщения: {$e->getMessage()}");
                    }
                }
            } else {
                // Если сообщений нет, подождать перед повторной проверкой
                sleep(1);
            }
        }
    }


    protected function processMessage(array $message): void
    {
        $planfixService = app(PlanfixService::class);
        $chatId = $message['chat_id'];
        $token = $message['token'];
        $telegramAccount = $planfixService->getIntegrationAndAccount($token);
        $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

        if (!empty($message['message'])) {
            $planfixService->sendMessage($madelineProto, $chatId, $message['message']);
        }

        if (!empty($message['attachments'])) {
            $attachments = json_decode($message['attachments'], true);
            $planfixService->sendAttachment($madelineProto, $chatId, $attachments, $message['message'] ?? null);
        }
    }
}
