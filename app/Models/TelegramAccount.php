<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $title
 * @property string $phone
 * @property int $telegram_id
 * @property string $session_path
 * @property int $user_id
 * @property string $status
 * @property int $message_rate
 * @property string $status_change_at
 *
 *
 */
class TelegramAccount extends Model
{
    protected $guarded = false;
}
