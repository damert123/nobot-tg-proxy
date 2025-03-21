<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $provider_id
 * @property string $planfix_token
 * @property int $telegram_account_id
 * @property string $token
 * @property string $name
 *
 */

class PlanfixIntegration extends Model
{
    protected $guarded = false;


    public function telegramAccount()
    {
        return $this->belongsTo(TelegramAccount::class);
    }
}
