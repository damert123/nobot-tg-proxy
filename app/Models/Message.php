<?php

namespace App\Models;

use danog\AsyncOrm\Serializer\Json;
use Illuminate\Database\Eloquent\Model;


/**
 * @property int chat_id
 * @property string token
 * @property string message
 * @property Json attachments
 * @property string status
 * @property string error_message
 * @property integer retry_count
 * @property string next_retry_at
 */

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'token',
        'message',
        'attachments',
        'status',
        'error_message',
        'retry_count',
        'next_retry_at'
    ];

}
