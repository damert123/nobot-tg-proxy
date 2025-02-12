<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * Class TgMessages
 *
 * @property int id
 * @property string provider_id
 * @property int chat_id
 * @property string planfix_token
 * @property string message
 * @property string title
 * @property int contact_id
 * @property string|null contact_name
 * @property string|null contact_last_name
 * @property string|null telegram_username
 * @property string|null contact_data
 * @property string|null attachments_name
 * @property string|null attachments_url
 * @property string status
 * @property string|null error_message
 */

class TgMessages extends Model
{


    protected $fillable = [
        'provider_id',
        'chat_id',
        'planfix_token',
        'message',
        'title',
        'contact_id',
        'contact_name',
        'contact_last_name',
        'telegram_username',
        'contact_data',
        'attachments_name',
        'attachments_url',
        'status',
        'error_message',
    ];

}
