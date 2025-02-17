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

    public static function create(array $data): TgMessagesEntity
    {
        $message = TgMessages::create([
            'provider_id' => $data['providerId'],
            'chat_id' => $data['chatId'],
            'planfix_token' => $data['planfix_token'],
            'message' => $data['message'] ?? 'Файл',
            'title' => $data['title'] ?? null,
            'contact_id' => $data['contactId'],
            'contact_name' => $data['contactName'] ?? null,
            'contact_last_name' => $data['contactLastName'] ?? null,
            'telegram_username' => $data['telegramUserName'] ?? null,
            'contact_data' => $data['contactData'] ?? null,
            'attachments_name' => $data['attachments[name]'] ?? null,
            'attachments_url' => $data['attachments[url]'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        return new self($message);
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
