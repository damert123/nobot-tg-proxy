<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int chat_id
 * @property string type_notification
 * @property string provider_id
 * @property string status
 *
 */
class NotificationPlanfix extends Model
{
    protected $guarded = false;
    protected $table = 'notification_planfixes';
    protected $fillable = [
        'chat_id',
        'type_notification',
        'provider_id',
        'status'
    ];
}
