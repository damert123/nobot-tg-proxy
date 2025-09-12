<?php

namespace App\Modules\QueueMessagesPlanfix;

use App\Models\Chat;
use App\Models\Message;
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
        $chat = Chat::firstOrCreate(['chat_id' => $chatId]);

        return new self($chat);
    }

    public function getChatId(): int
    {
        return $this->chat->chat_id;
    }

    public function getId(): int
    {
        return $this->chat->id;
    }

//    public static function hasInProgressMessages(string $chatId): bool
//    {
//        return MessageEntity::existsByChatIdAndStatus($chatId, 'in_progress');
//    }

    public static function getOrderByChatId(): ?string
    {
        $chat = Chat::query()->orderBy('created_at')->value('id');

        return $chat;
    }

    public static function getById(int $id): ?self
    {
        $chat = Chat::query()->find($id);

        return $chat ? new self($chat) : null;
    }


    public function getModel(): Chat
    {
        return $this->chat;
    }


    public function hasInProgressMessages(): bool
    {
        return MessageEntity::existsInProgressMessages($this);
    }

    public function hasWaitingRetryMessages(): bool
    {
        return MessageEntity::existsWaitingRetryMessages($this);
    }

    /**
     * @return self[]
     */
    public static function getAll(): array
    {
        $chats = Chat::get()->all();

        $entities = [];

        foreach ($chats as $chat) {
            $entities [] = new self($chat);
        }

        return $entities;
    }


    public function getFirstReadyRetryMessage(): ?MessageEntity
    {
        return MessageEntity::findFirstWaitingRetryByChatId($this->getId());

    }

    public function getFirstMessageInPending(): ?MessageEntity
    {
        return MessageEntity::findFirstPendingByChatId($this->getId());
    }

}
