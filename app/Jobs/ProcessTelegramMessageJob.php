<?php

namespace App\Jobs;

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

    protected $chatId;

    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    public function handle()
    {
        $chatId = $this->chatId;
        $streamKey = "stream:chat:$chatId";
        $lastId = '0';

        try {
            while (true) {
                $messages = Redis::command('XREADGROUP', [
                    'GROUP',
                    "group_$chatId",
                    "consumer_$chatId",
                    'COUNT',
                    1,
                    'BLOCK',
                    5000, // Ждём 5 секунд, если нет сообщений
                    'STREAMS',
                    $streamKey,
                    $lastId,
                ]);

                if (empty($messages)) {
                    Log::channel('queue-messages')->info("No messages in stream $streamKey.");
                    break;
                }

                foreach ($messages[0][1] as $entry) {
                    $id = $entry[0];
                    $data = json_decode($entry[1][1], true);

                    if (!$data) {
                        Log::channel('queue-messages')->error("Failed to decode message for $chatId: $entry");
                        continue;
                    }

                    Log::channel('queue-messages')->info("Processing message for chat $chatId: ", $data);

                    // Обработка сообщения
                    $this->processMessage($data, $chatId);

                    // Подтверждение обработки
                    Redis::command('XACK', [$streamKey, "group_$chatId", $id]);
                    $lastId = $id;
                }
            }
        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Error processing stream for chat $chatId: {$e->getMessage()}");
        }
    }

    protected function processMessage(array $data, int $chatId): void
    {
        // Инициализация MadelineProto и отправка сообщения
        try {
            $planfixService = app(PlanfixService::class);
            $token = $data['token'];
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            if (!empty($data['message'])) {
                $planfixService->sendMessage($madelineProto, $chatId, $data['message']);
            }

            if (!empty($data['attachments'])) {
                $planfixService->sendAttachment($madelineProto, $chatId, $data['attachments'], $data['message'] ?? null);
            }
        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Failed to process message for chat $chatId: {$e->getMessage()}");
        }
    }
}
