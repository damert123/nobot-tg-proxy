<?php

namespace App\Modules\QueueServiceMessagesToTelegram;

use Illuminate\Support\Carbon;

class QueueServiceMessagesEntity
{

    public const  STATUS_PENDING = 'pending';
    public const  STATUS_SENT = 'sent';
    public const  STATUS_PROCESSING = 'processing';
    const STATUS_FAILED = 'failed';
    private QueueServiceMessages $queueServiceMessages;

    public function __construct(QueueServiceMessages $queueServiceMessages)
    {
        $this->queueServiceMessages = $queueServiceMessages;

    }

    public static function getLastScheduledAt(): ?self
    {
        $lastScheduled  = QueueServiceMessages::where('status', 'pending')
            ->orderBy('scheduled_at', 'desc')
            ->first();

        if (!$lastScheduled) {
            return null;
        }

        return new self($lastScheduled);
    }

    public static function create(int $telegramId, string $message, string $telegramLink, Carbon $scheduledAt, string $status): self
    {
        $entity =  QueueServiceMessages::create([
            'telegram_id' => $telegramId,
            'message' => $message,
            'telegram_link' => $telegramLink,
            'scheduled_at' => $scheduledAt,
            'status' => $status
        ]);

        return new self($entity);

    }

    public static function getPendingMessageScheduled(): ?self
    {
        $message = QueueServiceMessages::where('status', self::STATUS_PENDING)
        ->where('scheduled_at', '<=', now())
        ->orderBy('scheduled_at', 'asc')
        ->first();

        if (!$message) {
            return null;
        }

        return new self($message);
    }


    public function getNextAvailableTime(): Carbon
    {
        return $this->getScheduledAt()->copy()->addMinutes(3);
    }

    public function getScheduledAt(): Carbon
    {
        return $this->queueServiceMessages->scheduled_at;

    }

    public function getQueueServiceMessages(): QueueServiceMessages
    {
        return $this->queueServiceMessages;
    }

    public function getId(): int
    {
        return $this->queueServiceMessages->id;
    }

    public function updateStatus(string $status): void
    {
        $this->queueServiceMessages->status = $status;

        $this->queueServiceMessages->saveOrFail();
    }

    public function getTelegramId(): int
    {
        return $this->queueServiceMessages->telegram_id;
    }

    public function getMessage(): string
    {
        return $this->queueServiceMessages->message;
    }

    public function getTelegramLink(): string
    {
        return $this->queueServiceMessages->telegram_link;
    }

}
