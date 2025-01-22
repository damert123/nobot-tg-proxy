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
    private MessageEntity $messageEntity;

    public $queue; // Устанавливаем очередь

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data, int $chatId, MessageEntity $messageEntity)
    {
        $this->data = $data;
        $this->chatId = $chatId;
        $this->messageEntity = $messageEntity;
    }

    /**
     * Execute the job.
     */
    public function handle(PlanfixService $planfixService): void
    {
        $this->messageEntity->setStatusInProgress();

        try {
            $token = $this->data['token'];
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            $chatId = $this->chatId;
            $message = $this->data['message'] ?? null;
            $id = $this->data['id'];


            if ($message) {
                $planfixService->sendMessage($madelineProto, $chatId, $message);
            }

            if (!empty($this->data['attachments'])) {
                $attachment = json_decode($this->data['attachments']);
                Log::channel('queue-messages')->info('МАССИВ ИЛИ НЕТ ?', $this->data['attachments']);
                $planfixService->sendAttachment($madelineProto, $chatId, $attachment, $message);
            }

            $this->messageEntity->setStatusCompleted();

        } catch (\Exception $e) {
            $this->messageEntity->setStatusError();
            Log::channel('queue-messages')->error("Ошибка в джобе: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Exception $exception)
    {
        Log::channel('queue-messages')->error("Ошибка выполнения джобы: {$exception->getMessage()}");

    }
}
