<?php

namespace App\Services\LineBot;

use App\Events\ThrowException;
use App\Models\GroupAdmin;
use App\Models\GroupConfig;
use Artisan;
use Carbon\Carbon;
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
     * @param string $message // 本次訊息，可用來觸發事件
     * @param array $options // api 參數
     */
    public function __construct($event, string $message = '', array $options = [])
    {
        $this->event   = $event;
        $this->options = $options;
        $this->message = $message;

        // groupId 為必須
        $this->groupId = $event['source']['groupId'] ?? null;

        if ($this->groupId == null) {
            throw new Exception('invalid groupId');
        }
    }

    /**
     * handle events
     *
     * @return $this
     */
    public function run()
    {
        if ($this->message == '') {
            return $this;
        }

        switch ($this->message) {
            case config('linebot.update_group'):
                // 更新群組資訊
                $options = [
                    'groupId'    => $this->groupId,
                    'replyToken' => $this->event['replyToken'],
                    'msg'        => '好的',
                ];
                Artisan::call('line:group:info', $options);
                $this->stopMsg();
                break;

            case config('linebot.claim_group_admin'):
                // 註冊群組的管理者
                $this->registerAdmin();
                break;

            default:
                // 最後才是一般文字訊息，檢查是否靜音
                $this->checkSilentMode();
                break;
        }

        return $this;
    }

    /**
     * 群組設定
     *
     * @return GroupConfig
     */
    public static function groupConfig($groupId)
    {
        $find = GroupConfig::where('group_id', $groupId)->first();

        if ($find == null) {
            $find           = new GroupConfig();
            $find->group_id = $groupId;
            $find->save();
        }

        return $find;
    }

    /**
     * 該群組的管理員
     *
     * @param string $groupId // 群組 id
     * @return GroupAdmin|null
     */
    public static function groupAdmin(string $groupId)
    {
        return GroupAdmin::where('group_id', function ($sub) use ($groupId) {
            return $sub->select('id')
                ->from('group_config')
                ->where('group_id', $groupId);
        })
            ->first();
    }

    /**
     * 驗證靜音模式
     * 目前只支援文字訊息的觸發
     *
     * @return $this
     */
    private function checkSilentMode()
    {
        $silentOn  = config('linebot.silent_on');
        $silentOff = config('linebot.silent_off');
        $config    = $this->groupConfig($this->groupId);

        if ($this->message == $silentOff && $config->silent_mode) {
            // 靜音 OFF
            $config->switchSilent();
            $this->options['--silent-off'] = true;
        } else if ($this->message == $silentOn && !$config->silent_mode) {
            // 靜音 ON
            $config->switchSilent();
            $this->options['--silent-on'] = true;
        } else if ($config->silent_mode) {
            $this->options = null;
        }
    }

    /**
     * 強制不再傳其他訊息
     *
     * @return void
     */
    private function stopMsg()
    {
        $this->options = null;
    }

    /**
     * 註冊群組管理者
     *
     * @return void
     */
    private function registerAdmin()
    {
        try {
            $this->options['--no-specific'] = true;
            $userId                         = $this->event['source']['userId'];
            $admin                          = $this->groupAdmin($this->groupId);

            // 註冊
            if ($admin == null) {
                $group             = $this->groupConfig($this->groupId);
                $find              = new GroupAdmin();
                $find->user_id     = $userId;
                $find->group_id    = $group->id;
                $find->is_sidekick = false;
                $find->applied_at  = Carbon::now();
                $find->save();

                $this->options['replyMsg'] = '主人~';
                return;
            }

            if ($admin != null && $admin->user_id != $userId) {
                $this->options['replyMsg'] = '朕 心有所屬，退下吧';
                return;
            }

            // 已是管理者
            $this->options['replyMsg'] = '別來調戲朕';
        } catch (Exception $e) {
            $this->options['replyMsg'] = '罐罐不夠多，更新管理員失敗';
            event(new ThrowException($e));
        }
    }
}
