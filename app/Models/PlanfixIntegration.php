<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanfixIntegration extends Model
{
    protected $guarded = false;


    public function telegramAccount()
    {
        return $this->belongsTo(TelegramAccount::class);
    }
}
