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

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */

    protected $data;
    protected $chatId;

    public $timeout = 300;

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
        Log::channel('queue-messages')->info("Получены данные 2");
        $this->messageEntity->setStatusInProgress();


        try {
            Log::info("Получены данные 1");
            $token = $this->data['token'];
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            $chatId = $this->chatId;
            $message = $this->data['message'] ?? null;
            $id = $this->data['id'];


            if (!empty($this->data['attachments'])) {
                $attachments = $this->data['attachments'];
                $attachments = json_decode($attachments, true);
                $planfixService->sendAttachment($madelineProto, $chatId, $attachments, $message, $telegramAccount);
            } else{
                $planfixService->sendMessage($madelineProto, $chatId, $message, $telegramAccount);
            }




            $this->messageEntity->setStatusCompleted();

        } catch (\Throwable $e) {
            $this->messageEntity->setStatusError($e->getMessage());
            Log::channel('queue-messages')->error("Ошибка в джобе (попытка {$this->attempts()}): {$e->getMessage()}");
        }
    }


}
