<?php

namespace App\Jobs;

use App\Exceptions\PeerFloodException;
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
        Log::channel('queue-messages')->info("ProcessTelegramMessageJob: start", ['chatId' => $this->chatId]);

        $this->messageEntity->setStatusInProgress();


        try {
            $this->doSend($planfixService);
            $this->messageEntity->setStatusCompleted();
            Log::channel('queue-messages')->info("ProcessTelegramMessageJob: completed", ['chatId' => $this->chatId]);

        }
        catch (PeerFloodException $e){
            $this->handlePeerFlood($e);
        }

        catch (\Throwable $e) {
            $this->messageEntity->setStatusError($e->getMessage());
            Log::channel('queue-messages')->error("Ошибка в джобе (попытка {$this->attempts()}): {$e->getMessage()}");
        }
    }

    /**
     *
     * @throws \App\Exceptions\PeerFloodException
     * @throws \Throwable
     */


    private function doSend(PlanfixService $planfixService): void
    {
        Log::info("Получены данные 2");
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

    }


    private function handlePeerFlood(PeerFloodException $e)
    {
        $this->messageEntity->setStatusError($e->getMessage());
        SendPeerFloodNotificationToPlanfixJob::dispatch(
            $this->data['planfix_token'],
            $this->data['chatId'],
            $this->data['providerId'],
        )->onQueue('planfix');

        Log::channel('queue-messages')->warning(
            "PeerFlood detected, dispatched SendPeerFloodNotificationJob",
            ['chatId' => $this->chatId, 'error' => $e->getMessage()]
        );
    }


}
