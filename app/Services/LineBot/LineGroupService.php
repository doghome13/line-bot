<?php

namespace App\Services\LineBot;

use App\Models\GroupConfig;
use Exception;

class LineGroupService
{
    /**
     * api 參數
     *
     * @var array
     */
    public $options;

    /**
     * 群組事件
     *
     * @param mixed $event
     * @param array $options // api 參數
     */
    public function __construct($event, array $options = [])
    {
        $this->event   = $event;
        $this->options = $options;

        // groupId 為必須
        $this->groupId = $event['source']['groupId'] ?? null;

        if ($this->groupId == null) {
            throw new Exception('invalid groupId');
        }
    }

    /**
     * 驗證靜音模式
     * 目前只支援文字訊息的觸發
     *
     * @param string $text
     * @return $this
     */
    public function checkSilentMode(string $text)
    {
        $silentOn  = config('services.linebot.silent_on');
        $silentOff = config('services.linebot.silent_off');
        $config    = $this->groupConfig();

        if ($text == $silentOff && $config->silent_mode) {
            // 靜音 OFF
            $config->switchSilent();
            $this->options['--silent-off'] = true;
        } else if ($text == $silentOn && !$config->silent_mode) {
            // 靜音 ON
            $config->switchSilent();
            $this->options['--silent-on'] = true;
        } else if ($config->silent_mode) {
            $this->options = null;
        }

        return $this;
    }

    /**
     * 群組設定
     *
     * @return GroupConfig
     */
    private function groupConfig()
    {
        $find = GroupConfig::where('group_id', $this->groupId)->first();

        if ($find == null) {
            $find           = new GroupConfig();
            $find->group_id = $this->groupId;
            $find->save();
        }

        return $find;
    }
}
