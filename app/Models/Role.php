<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = false;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);

    }
}
