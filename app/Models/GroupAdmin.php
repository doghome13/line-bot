<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAdmin extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $table = 'group_admin';

    protected $casts = [
        'group_id'    => 'integer',
        'user_id'     => 'string',
        'is_sidekick' => 'boolean',
        'applied_at'  => 'datetime:Y-m-d H:i:s',
    ];
}
