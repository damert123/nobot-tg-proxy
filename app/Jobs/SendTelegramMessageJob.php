<?php

namespace App\Jobs;

use App\Services\TopUpSendMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    protected int $telegramId;
    protected string $message;
    protected string $telegramLink;

    public $timeout = 130;


    public function __construct(int $telegramId, string $message, string $telegramLink)
    {
        $this->telegramId = $telegramId;
        $this->message = $message;
        $this->telegramLink = $telegramLink;
    }
    /**
     * Execute the job.
     */
    public function handle(TopUpSendMessageService $topUpService): void
    {
        Log::channel('top-up-messages')->info("SendTelegramMessageJob запущен", [
            'telegramId' => $this->telegramId,
            'link' => $this->telegramLink,
        ]);

        $status = $topUpService->sendMessageDirectly(
            $this->telegramId,
            $this->message,
            $this->telegramLink
        );

        Log::channel('top-up-messages')->info("SendTelegramMessageJob завершён", [
            'status' => $status,
        ]);
    }
}
