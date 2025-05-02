<?php

namespace App\Jobs;

use App\Modules\PlanfixNotification\NotificationEntity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendErrorNotificationToPlanfix implements ShouldQueue
{
    use Queueable;


    protected $planfixToken;
    protected $chatId;
    protected $providerId;
    protected $errorMessage;


    /**
     * Create a new job instance.
     */
    public function __construct(string $planfixToken, int $chatId, string $providerId, string $errorMessage)
    {
        $this->planfixToken = $planfixToken;
        $this->chatId = $chatId;
        $this->providerId = $providerId;
        $this->errorMessage = $errorMessage;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = NotificationEntity::buildPayloadForError(
            $this->planfixToken,
            $this->chatId,
            $this->providerId,
            NotificationEntity::ERROR_MESSAGE_DEFAULT
        );


        Log::channel('queue-messages')->info('ARRAY: ' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


        try {
            $response = Http::asForm()
                ->post('https://agencylemon.planfix.ru/webchat/api', $payload);

            if ($response->successful()) {
                NotificationEntity::create(
                    $this->chatId,
                    $this->providerId,
                    NotificationEntity::TYPE_ERROR . $this->errorMessage,
                    NotificationEntity::STATUS_SUCCESS
                );
            } else {
                NotificationEntity::create(
                    $this->chatId,
                    $this->providerId,
                    NotificationEntity::TYPE_ERROR . $this->errorMessage,
                    NotificationEntity::STATUS_FAIL . $response->status()
                );
                Log::channel('queue-messages')->error('Ошибка при отправке уведомления в Planfix', [
                    'chatId' => $this->chatId,
                    'providerId' => $this->providerId,
                    'planfixToken' => $this->planfixToken,
                ]);
            }

        } catch (\Throwable $e) {
            NotificationEntity::create(
                $this->chatId,
                $this->providerId,
                NotificationEntity::TYPE_ERROR . $this->errorMessage,
                NotificationEntity::STATUS_ERROR . ' ' . $e->getMessage()
            );

            Log::channel('queue-messages')->error('Ошибка при отправке уведомления в Planfix', [
                'chatId' => $this->chatId,
                'providerId' => $this->providerId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
