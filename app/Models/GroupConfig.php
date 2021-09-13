<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupConfig extends Model
{
    protected $table = 'group_config';

    protected $casts = [
        'group_id'      => 'string',
        'need_sidekick' => 'boolean',
        'silent_mode'   => 'boolean',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    // protected $hidden = ['id'];

    /**
     * 靜音模式 on/off
     */
    public function switchSilent()
    {
        $this->silent_mode = !$this->silent_mode;
        $this->save();
    }
}
