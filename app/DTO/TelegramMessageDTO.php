<?php

namespace App\DTO;

class TelegramMessageDTO
{

    public function __construct(
        public int $fromId,
        public int $toId,
        public string $message
    )
    {}

    public static function fromArray (array $data): self
    {
        return new self(
            fromId: $data['from_id'],
            toId:  $data['to_id'],
            message: $data['message']
        );
    }
}