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

    protected string $chatId;

    public $timeout = 300;

    public $queue; // Устанавливаем очередь

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(string $chatId)
    {
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(PlanfixService $planfixService): void
    {
        $chatId = $this->chatId;
        $queueKey = "queue:chat:$chatId";
        $lockKey = "lock:chat:$chatId";

        try {

            $messageData = Redis::command('LPOP', [$queueKey]);
            if (!$messageData) {
                // Если очередь пуста, снимаем блокировку
                Redis::command('del', [$lockKey]);
                return;
            }

            $data = json_decode($messageData, true);

            Log::channel('queue-messages')->error("MESSAGE DATA: $messageData");
            Log::channel('queue-messages')->error("DATA: $messageData");

            if ($data === null) {
                throw new \Exception('Failed to decode message data from Redis: ' . json_last_error_msg());
            }

            if (!isset($data['token'])) {
                throw new \Exception('Token is missing in the job data.');
            }

            $token = $data['token'];
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);


            $message = $data['message'] ?? null;

            if ($message) {
                $planfixService->sendMessage($madelineProto, $chatId, $message);
            }


            if (!empty($this->data['attachments'])) {
                $planfixService->sendAttachment($madelineProto, $chatId, $data['attachments'], $message);
            }

            if (Redis::command('LLEN', [$queueKey]) > 0) {
                ProcessTelegramMessageJob::dispatch($chatId);
            } else {
                // Если очередь пуста, снимаем блокировку
                Redis::command('del', [$lockKey]);
            }

        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Ошибка в джобе: {$e->getMessage()}");
            throw $e;
        }finally {
            Redis::command('del', [$lockKey]);
        }
    }

    public function failed(\Exception $exception)
    {
        $lockKey = "lock:chat:{$this->chatId}";

        // Снимаем блокировку при ошибке
        Redis::command('del', [$lockKey]);

        Log::channel('queue-messages')->error("Ошибка выполнения джобы: {$exception->getMessage()}");

    }
}
