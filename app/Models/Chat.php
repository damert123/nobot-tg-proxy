<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int chat_id
 */


class Chat extends Model
{
    protected $fillable = [
        'chat_id'
    ];
}
