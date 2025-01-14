<?php

namespace App\Jobs;

use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTelegramMessage implements ShouldQueue
{
    use Queueable;


    protected $data;

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

        $token = $data['token'];
        $telegramAccount = $this->planfixService->getIntegrationAndAccount($token);

        $madelineProto = $this->planfixService->initializeModelineProto($telegramAccount->session_path);

        $chatId = $data['chatId'];
        $message = $data['message'] ?? null;

        if ($message){
            $this->planfixService->sendMessage($madelineProto, $chatId, $message);
        }

        if (!empty($data['attachments'])){
            $this->planfixService->sendAttachment($madelineProto, $chatId, $data['attachments'], $message );
        }
    }
}
