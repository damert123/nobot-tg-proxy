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


    public static function getToken(string $token)
    {
        return PlanfixIntegration::where('token', $token)->first();
    }

    public static function getTelegramFromId(int $id)
    {
        return TelegramAccount::where('id', $id)->first();
    }
}
