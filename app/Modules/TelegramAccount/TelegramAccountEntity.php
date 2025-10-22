<?php

namespace App\Modules\TelegramAccount;


use App\Models\TelegramAccount;


class TelegramAccountEntity
{

    public const PAUSE = 'Пауза';
    public const ACCOUNT_NOT_AUTH = 'Разлогинен';
    public const ACCOUNT_BANNED = 'Забанен';
    private TelegramAccount $telegramAccount;

    public function __construct(TelegramAccount $telegramAccount)
    {
        $this->telegramAccount = $telegramAccount;
    }


    public static function findTestSession(): ?self
    {
        $testAccount = TelegramAccount::where('telegram_id', 6367480105)->first();

        return $testAccount ? new self($testAccount) : null;
    }

    public static function getBySessionPath(string $sessionPath): ?self
    {
        $account = TelegramAccount::where('session_path', $sessionPath)->first();

        return $account ? new self($account) : null;
    }

    public function changeStatus(string $status): void
    {
        $this->telegramAccount->status = $status;

        $this->telegramAccount->saveOrFail();
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

    public function getId(): int
    {
        return $this->telegramAccount->id;
    }

    public function getPhone(): string
    {

        return $this->telegramAccount->phone;
    }

    public function getStatus(): string
    {
        return $this->telegramAccount->status;
    }

    public function inPause(): bool
    {
        return $this->telegramAccount->status === 'Пауза';
    }


}
