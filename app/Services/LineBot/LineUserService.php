<?php

namespace App\Services\LineBot;

use App\Models\GroupAdmin;
use App\Models\GroupConfig;

class LineUserService
{
    /**
     * api 參數
     *
     * @var array
     */
    public $options;

    /**
     * 用戶
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
        $this->userId  = $event['source']['userId'] ?? null;
    }

    /**
     * handle events
     *
     * @return $this
     */
    public function run()
    {
        if ($this->message == '' || $this->userId == null) {
            $this->stopMsg();
            return $this;
        }

        switch ($this->message) {
            case config('linebot.review_group_sidekick'):
                // 審核(所有)小幫手的申請，先列出管理的群組
                $this->findGroupByAdmin();
                break;

            default:
                // 預設是不回覆訊息
                $this->stopMsg();
                break;
        }

        return $this;
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
     * 該用戶為主要管理者的群組
     *
     * @return void
     */
    private function findGroupByAdmin()
    {
        $groups = GroupConfig::select(['id', 'name', 'picture_url'])
            ->whereIn('id', function ($sub) {
                return $sub->select('group_id')
                    ->from('group_admin')
                    ->where('is_sidekick', false)
                    ->where('user_id', $this->userId);
            })
            ->get();

        if ($groups->count() == 0) {
            $this->stopMsg();
            return;
        }

        // 回傳格式 JSON
        $this->options['replyMsg'] = $groups->toJson();
        $this->options['--template-confirm'] = true;
    }

    /**
     * 該用戶所屬的小幫手，審核申請
     *
     * @return void
     */
    private function reviewSidekickApply()
    {
        $sidekicks = GroupAdmin::where('is_sidekick', true)
            ->where('applied', true)
            ->whereIn('group_id', function ($sub) {
                // 主要管理者才有的權限
                return $sub->select('group_id')
                    ->from('group_admin')
                    ->where('user_id', $this->userId)
                    ->where('is_sidekick', false);
            })
            ->get();

        if ($sidekicks->count() == 0) {
            $this->stopMsg();
            return;
        }

        $output = [];

        foreach ($sidekicks as $sidekick) {
            $data = [
                //
            ];
        }
    }
}
