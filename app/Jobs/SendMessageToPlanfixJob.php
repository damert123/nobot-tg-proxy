<?php

namespace App\Jobs;

use App\Modules\TelegramMessagesToPlanfix\TgMessagesEntity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendMessageToPlanfixJob implements ShouldQueue
{
    use Queueable;



    protected $data;
    private TgMessagesEntity $tgMessagesEntity;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, TgMessagesEntity $tgMessagesEntity)
    {
        $this->data = $data;
        $this->tgMessagesEntity = $tgMessagesEntity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messageRecord = TgMessagesEntity::create($this->data);

        try {
            $response = Http::asForm()->post('https://agencylemon.planfix.ru/webchat/api', $this->data);

            if ($response->successful()) {
                $messageRecord->setStatusComplete();
            } else {
                $responseBody = $response->body();
                $responseStatus = $response->status();
                $messageRecord->setStatusError(" code: $responseStatus body:  $responseBody");
            }
        } catch (\Exception $e) {
            $messageRecord->setStatusError($e->getMessage());
        }
    }
}
