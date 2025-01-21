<?php

namespace App\Modules\QueueMessagesPlanfix;

use App\Models\Chat;
use phpseclib3\File\ASN1\Maps\Attribute;

class ChatEntity
{

    private Chat $chat;
    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }


    public static function setChat(string $chatId): self
    {
        $chat = Chat::firstOrCreate(['id' => $chatId]);

        return new self($chat);
    }

    public function getId(): int
    {
        return $this->chat->id;
    }

    public static function hasInProgressMessages(string $chatId): bool
    {
        return MessageEntity::existsByChatIdAndStatus($chatId, 'in_progress');
    }

    public static function getOrderByChatId(): ?string
    {
        $chat = Chat::query()->orderBy('created_at')->value('id');

        return $chat;
    }

    public function getModel(): Chat
    {
        return $this->chat;
    }




}
