<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 *
 *
 */

class MessagePlanfix extends Model
{
    protected $fillable = [
        'chat_id',
        'token',
        'message',
        'attachments',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];
}
