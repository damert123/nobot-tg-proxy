<?php

namespace App\Jobs;

use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    protected $chatId;

    public $timeout = 300;

    public $queue; // Устанавливаем очередь

    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    public function handle(PlanfixService $planfixService): void
    {
        $chatId = $this->chatId;
        $streamKey = "stream:chat:$chatId";
        $lastId = '0';

        try {
            while (true) {
                // Read one message from the stream using Redis::command
                $messages = Redis::command('XREAD', ['STREAMS', $streamKey, $lastId]);

                if (empty($messages)) {
                    Log::channel('queue-messages')->info("No messages in stream $streamKey, exiting.");
                    return; // Exit if no messages
                }

                foreach ($messages[0][1] as $entry) {
                    $id = $entry[0];
                    $lastId = $id;
                    $data = json_decode($entry[1][1], true);

                    if (!$data) {
                        Log::channel('queue-messages')->error("Failed to decode message from stream $streamKey: $entry");
                        continue;
                    }

                    Log::channel('queue-messages')->info("Processing message for chat $chatId: ", $data);

                    // Process the message
                    $token = $data['token'];
                    $telegramAccount = $planfixService->getIntegrationAndAccount($token);
                    $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

                    $message = $data['message'] ?? null;
                    if ($message) {
                        $planfixService->sendMessage($madelineProto, $chatId, $message);
                    }

                    if (!empty($data['attachments'])) {
                        $planfixService->sendAttachment($madelineProto, $chatId, $data['attachments'], $message);
                    }

                    // Acknowledge message processing completion
                    Redis::command('XACK', [$streamKey, "group_$chatId", $id]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Error processing chat $chatId stream: {$e->getMessage()}");
            throw $e;
        }
    }
}
