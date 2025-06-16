<?php

namespace App\Modules\TelegramAccount;


use App\Models\TelegramAccount;


class TelegramAccountEntity
{
    private TelegramAccount $telegramAccount;

    public function __construct(TelegramAccount $telegramAccount)
    {
        $this->telegramAccount = $telegramAccount;
    }


    public static function findTestSession(): ?self
    {
        $testAccount = TelegramAccount::where('telegram_id', 7410139849)->first();

        return $testAccount ? new self($testAccount) : null;

    }

    public function getTelegramId(): int
    {
        return $this->telegramAccount->telegram_id;
    }

    public function getSessionPath(): string
    {
        return $this->telegramAccount->session_path;
    }

    public function getModel(): TelegramAccount
    {
        return $this->telegramAccount;
    }


}
