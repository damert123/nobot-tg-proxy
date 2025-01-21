<?php

namespace App\Jobs;

use App\Modules\QueueMessagesPlanfix\MessageEntity;
use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    protected $data;
    protected $chatId;

    public $queue; // Устанавливаем очередь

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data, int $chatId)
    {
        $this->data = $data;
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(PlanfixService $planfixService): void
    {

        try {
            $token = $this->chatId;
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            $chatId = $this->data['chat_id'];
            $message = $this->data['message'] ?? null;
            $id = $this->data['id'];

            if ($message) {
                $planfixService->sendMessage($madelineProto, $chatId, $message);
            }

            if (!empty($this->data['attachments'])) {
                $planfixService->sendAttachment($madelineProto, $chatId, $this->data['attachments'], $message);
            }

            $messageEntity = MessageEntity::getMessageById($id);
            if ($messageEntity) {
                $messageEntity->updateStatus('completed');
            } else {
                Log::channel('queue-messages')->warning("Сообщение с ID $id не найдено.");
            }

        } catch (\Exception $e) {
            Log::channel('queue-messages')->error("Ошибка в джобе: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        Log::channel('queue-messages')->error("Ошибка выполнения джобы: {$exception->getMessage()}");

    }
}
