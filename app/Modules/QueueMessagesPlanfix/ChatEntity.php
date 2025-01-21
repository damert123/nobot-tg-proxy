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
        $chat = Chat::firstOrCreate(['chat_id' => $chatId]);

        return new self($chat);
    }

    public function getChatId(): int
    {
        return $this->chat->chat_id; // Возвращаем ID чата из модели
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
        $chat = Chat::query()->find($id); // Используем find для поиска по первичному ключу

        return $chat ? new self($chat) : null; // Возвращаем экземпляр ChatEntity или null
    }


    public function getModel(): Chat
    {
        return $this->chat;
    }


    public function hasInProgressMessages(): bool
    {
        return MessageEntity::existsInProgressMessages($this);
    }

    /**
     * @return self[]
     */
    public static function getAll(): array
    {
        $chats = Chat::get()->all();

        $entities = [];

        foreach ($chats as $chat) {
            $entities = new self($chat);
        }

        return $entities;
    }

    public function getFirstMessageInPending(): ?MessageEntity
    {
        return MessageEntity::findFirstPendingByChatId($this->getId());
    }
}
