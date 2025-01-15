<?php

namespace App\Jobs;

use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    public $timeout = 300;

    public $queue; // Устанавливаем очередь

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(PlanfixService $planfixService): void
    {

        try {
            $token = $this->data['token'];
            $telegramAccount = $planfixService->getIntegrationAndAccount($token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            $chatId = $this->data['chatId'];
            $message = $this->data['message'] ?? null;

            if ($message) {
                $planfixService->sendMessage($madelineProto, $chatId, $message);
            }


            if (!empty($this->data['attachments'])) {
                $planfixService->sendAttachment($madelineProto, $chatId, $this->data['attachments'], $message);
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
