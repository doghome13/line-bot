<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogException extends Model
{
    const UPDATED_AT = null;

    protected $table = 'log_exception';

    protected $casts = [
        'code'       => 'integer',
        'line'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = ['id'];

    public function setUrlAttribute($value)
    {
        $this->attributes['url'] = substr($value, 0, 200);
    }

    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = substr($value, 0, 1000);
    }
}
