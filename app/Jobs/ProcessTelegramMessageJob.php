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
        Redis::connection()->client()->xGroup(
            'CREATE',
            $this->streamKey, // Ключ потока
            $group,           // Имя группы
            '0',              // ID, с которого начинать чтение
            true              // MKSTREAM: создаем поток, если его еще нет
        );
        while (true) {
            $messages = Redis::connection()->client()->xReadGroup(
                $group,         // Имя группы
                $consumer,      // Имя потребителя
                [$this->streamKey => '>'], // Ключи потока и позиция чтения
                1,              // Максимальное количество сообщений
                5000            // Блокировка чтения (в миллисекундах)
            );
            if (!empty($messages[$this->streamKey])) {
                foreach ($messages[$this->streamKey] as $id => $message) {
                    try {
                        $this->processMessage($message);

                        // Подтверждаем обработку сообщения
                        Redis::connection()->client()->xAck(
                            $this->streamKey, // Ключ потока
                            $group,           // Имя группы
                            [$id]             // ID сообщения
                        );
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
