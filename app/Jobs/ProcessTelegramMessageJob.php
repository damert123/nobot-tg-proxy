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
        $queueKey = "queue:chat:$chatId";
        $lockKey = "lock:chat:$chatId";

        try {
            // Устанавливаем блокировку
            Redis::command('set', [$lockKey, true, 'EX', 300]);

            while (true) {
                $messageData = Redis::command('LPOP', [$queueKey]);

                if (!$messageData) {
                    // Очередь пуста — снимаем блокировку
                    Redis::command('del', [$lockKey]);
                    break;
                }

                $data = json_decode($messageData, true);

                if ($data === null) {
                    throw new \Exception('Failed to decode message data: ' . json_last_error_msg());
                }

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
            }
        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Ошибка в джобе: {$e->getMessage()}");
            throw $e;
        } finally {
            // Снимаем блокировку
            Redis::command('del', [$lockKey]);
        }
    }

    public function failed(\Exception $exception)
    {
        $chatId = $this->chatId;
        $lockKey = "lock:chat:$chatId";

        // Снимаем блокировку при ошибке
        Redis::command('del', [$lockKey]);

        Log::channel('queue-messages')->error("Ошибка выполнения джобы: {$exception->getMessage()}");
    }
}
