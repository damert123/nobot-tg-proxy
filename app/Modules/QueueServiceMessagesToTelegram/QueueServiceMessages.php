<?php

namespace App\Modules\QueueServiceMessagesToTelegram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int id
 * @property int telegram_id
 * @property string message
 * @property string telegram_link
 * @property string status
 * @property Carbon scheduled_at
 *
 */

class QueueServiceMessages extends Model
{
    protected $fillable = [
        'telegram_id',
        'message',
        'telegram_link',
        'status',
        'scheduled_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime'
    ];
}
