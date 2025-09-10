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
        $testAccount = TelegramAccount::where('telegram_id', 7410139849)->first();

        return $testAccount ? new self($testAccount) : null;

    }

    public static function getBySessionPath(string $sessionPath): ?self
    {
        $account = TelegramAccount::where('session_path', $sessionPath)->first();

        return $account ? new self($account) : null;
    }

    public static function getAllActiveAccountTgToCrm()
    {
        return TelegramAccount::query()
            ->join('planfix_integrations', 'telegram_accounts.id', '=', 'planfix_integrations.telegram_account_id')
            ->whereNotNull('telegram_accounts.session_path')
            ->whereNot('telegram_accounts.status',self::PAUSE)
            ->whereNot('telegram_accounts.status',self::ACCOUNT_NOT_AUTH)
            ->get()
            ->map(fn($account) => new self($account));
    }

    public  function updateMessageRate(int $count)
    {
        $this->telegramAccount->message_rate = $count;

        $this->telegramAccount->save();

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


}
