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
 *
 *
 */
class TelegramAccount extends Model
{
    protected $guarded = false;
}
