<?php

namespace App\Modules\QueueMessagesPlanfix;

use App\Models\Message;

class MessageEntity
{
    private Message $message;
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public static function findByChatIdAndStatus(int $chatId, string $status)
    {
        $messages =  Message::where('chat_id', $chatId)
            ->where('status', $status)
            ->orderBy('created_at')
            ->get();


        return $messages->map(fn($message) => new self($message))->toArray();

    }

    public static function existsByChatIdAndStatus(int $chatId, string $status): bool
    {
        return Message::query()
            ->where('chat_id', $chatId)
            ->where('status', $status)
            ->exists();
    }

    public static function findFirstByChatIdAndStatus(int $chatId, string $status): ?self
    {
        $message = Message::query()
            ->where('chat_id', $chatId)
            ->where('status', $status)
            ->orderBy('created_at')
            ->first();

        return $message ? new self($message) : null;
    }


    public function updateStatus(string $status): bool
    {
        $this->message->status = $status;
        return $this->message->saveOrFail();
    }



}
