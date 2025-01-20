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

    public static function hasInProgressMessages(string $chatId ): bool
    {
        return MessageEntity::existsByChatIdAndStatus($chatId, 'in_progress');
    }



}
