<?php

namespace App\Jobs;

use App\Modules\PlanfixNotification\NotificationEntity;
use App\Modules\TelegramMessagesToPlanfix\TgMessagesEntity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPeerFloodNotificationToPlanfixJob implements ShouldQueue
{
    use Queueable;


    protected $planfixToken;
    protected $chatId;
    protected $providerId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $planfixToken, int $chatId, string $providerId)
    {
        $this->planfixToken = $planfixToken;
        $this->chatId = $chatId;
        $this->providerId = $providerId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $payload = NotificationEntity::buildPayloadForPeerFlood(  // Делаю тело запроса для отправки уведомеления
            $this->planfixToken,
            $this->chatId,
            $this->providerId
        );

        try {
            $response = Http::asForm()
                ->post('https://agencylemon.planfix.ru/webchat/api', $payload);

            if ($response->successful()) {
                NotificationEntity::create(
                    $this->chatId,
                    $this->providerId,
                    NotificationEntity::TYPE_PEER_FLOOD,
                    NotificationEntity::STATUS_SUCCESS
                );
            } else {
                NotificationEntity::create(
                    $this->chatId,
                    $this->providerId,
                    NotificationEntity::TYPE_PEER_FLOOD,
                    NotificationEntity::STATUS_FAIL . $response->status()
                );
            }

        } catch (\Throwable $e) {
            NotificationEntity::create(
                $this->chatId,
                $this->providerId,
                NotificationEntity::TYPE_PEER_FLOOD,
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
