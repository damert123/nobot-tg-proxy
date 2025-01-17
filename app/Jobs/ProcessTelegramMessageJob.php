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
            // Проверяем и устанавливаем блокировку
            if (!Redis::command('set', [$lockKey, true, 'NX', 'EX', 300])) {
                Log::channel('queue-messages')->info("Очередь $chatId уже обрабатывается другим воркером.");
                return;
            }

            Log::channel('queue-messages')->info("Начата обработка очереди для чата $chatId");

            while (true) {
                // Используем BRPOP с таймаутом
                $messageData = Redis::command('BRPOP', [$queueKey, 10]);

                if (!$messageData) {
                    Log::channel('queue-messages')->info("Очередь пуста для чата $chatId.");
                    break;
                }

                $data = json_decode($messageData[1], true);

                if ($data === null) {
                    throw new \Exception('Ошибка декодирования данных: ' . json_last_error_msg());
                }

                Log::channel('queue-messages')->info("Успешно извлечены данные из Redis: ", $data);

                // Обработка сообщения
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
            Log::channel('queue-messages')->info("Обработка очереди завершена для чата $chatId.");
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
