<?php

namespace App\Modules\PlanfixIntegration;

use App\Models\PlanfixIntegration;
use App\Models\TelegramAccount;

class PlanfixIntegrationEntity
{
    private PlanfixIntegration $planfixIntegration;

    public function __construct(PlanfixIntegration $planfixIntegration)
    {
        $this->planfixIntegration = $planfixIntegration;
    }


    public static function findByToken(string $token): ?self
    {
        return new self (PlanfixIntegration::where('token', $token)->first());
    }

    public static function getTelegramFromId(int $id)
    {
        return TelegramAccount::where('id', $id)->first();
    }

    public static function findByTelegramAccountId(int $id): self
    {
        $planfix =  PlanfixIntegration::where('telegram_account_id', $id)->first();

        return new self($planfix);
    }

    public function getTelegramAccountId(): int
    {
        return $this->planfixIntegration->telegram_account_id;
    }

    public function getProviderId(): string
    {
        return $this->planfixIntegration->provider_id;
    }

    public function getId(): int
    {
        return $this->planfixIntegration->id;
    }

    public function getPlanfixToken(): string
    {
        return $this->planfixIntegration->planfix_token;
    }

    public function getToken(): string
    {
        return $this->planfixIntegration->token;
    }


}
