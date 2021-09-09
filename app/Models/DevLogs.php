<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevLogs extends Model
{
    const UPDATED_AT = null;

    protected $table = 'dev_logs';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = ['id'];

    // 確保寫入讀取的格式一致
    public function setMsgAttribute($value)
    {
        $this->attributes['msg'] = json_encode($value);
    }

    public function getMsgAttribute()
    {
        return json_decode($this->attributes['msg']);
    }
}
