<?php

namespace App\Modules\TelegramAccount;


use App\Models\TelegramAccount;
use App\Modules\PlanfixIntegration\PlanfixIntegrationEntity;
use Illuminate\Support\Carbon;


class TelegramAccountEntity
{

    public const PAUSE = 'Пауза';
    public const STATUS_ACTIVE = 'Активен';
    public const ACCOUNT_NOT_AUTH = 'Разлогинен';
    public const ACCOUNT_BANNED = 'Забанен';
    public const STATUS_THROTTLED = 'THROTTLED';
    public const STATUS_BROADCAST = 'BROADCAST';
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
      $planfixIds = PlanfixIntegrationEntity::getAllTelegramAccountId();

        return TelegramAccount::query()
            ->whereIn('id', $planfixIds)
            ->whereNotNull('session_path')
            ->whereNotIn('status', [TelegramAccountEntity::PAUSE, TelegramAccountEntity::ACCOUNT_NOT_AUTH])
            ->get()
            ->map(fn($account) => new self($account));

    }

    public static function getTelegramAccount(string $token): self
    {
        $tgId = PlanfixIntegrationEntity::findByToken($token)->getTelegramAccountId();

        return self::getById($tgId);

    }


    public  function updateMessageRate(int $count)
    {
        $this->telegramAccount->message_rate = $count;

        $this->telegramAccount->save();

    }

    public function updateStatus(string $status, Carbon $status_change_at)
    {
        $this->telegramAccount->status = $status;
        $this->telegramAccount->status_change_at = $status_change_at;

        $this->telegramAccount->saveOrFail();
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

    public function getStatus(): string
    {
        return  $this->telegramAccount->status;
    }

    public function getStatusChangeAt(): ?string
    {
        return  $this->telegramAccount->status_change_at;
    }

    public static function getById(int $id): ?self
    {
        $account = TelegramAccount::where('id', $id)->find();

        return $account ? new self($account) : null;
    }


}
