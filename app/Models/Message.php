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
 */

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'token',
        'message',
        'attachments',
        'status'
    ];
}
