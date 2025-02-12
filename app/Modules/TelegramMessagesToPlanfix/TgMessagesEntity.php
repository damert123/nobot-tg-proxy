<?php

namespace App\Modules\TelegramMessagesToPlanfix;

use App\Models\TgMessages;

class TgMessagesEntity
{

    private const IN_PROGRESS = 'in_progress';

    const COMPLETE = 'complete';

    const ERROR = 'error';

    private TgMessages $tgMessages;

    public function __construct(TgMessages $tgMessages)
    {
        $this->tgMessages = $tgMessages;
    }

    public function create(array $data): TgMessages
    {
        return $this->tgMessages->create([
            'provider_id' => $data['provider_id'],
            'chat_id' => $data['chat_id'],
            'planfix_token' => $data['planfix_token'],
            'message' => $data['message'] ?? 'Файл',
            'title' => $data['title'],
            'contact_id' => $data['contact_id'],
            'contact_name' => $data['contact_name'] ?? null,
            'contact_last_name' => $data['contact_last_name'] ?? null,
            'telegram_username' => $data['telegram_username'] ?? null,
            'contact_data' => $data['contact_data'] ?? null,
            'attachments_name' => $data['attachments_name'] ?? null,
            'attachments_url' => $data['attachments_url'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);
    }

    public function setStatusComplete(): void
    {
        $this->tgMessages->status = self::COMPLETE;

        $this->tgMessages->saveOrFail();
    }

    public function setStatusError(string $errorMessage = null): void
    {
        $this->tgMessages->status = self::ERROR;
        $this->tgMessages->error_message = $errorMessage;
        $this->tgMessages->saveOrFail();
    }


}
