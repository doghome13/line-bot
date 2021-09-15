<?php

namespace App\Services\LineBot;

use App\Models\GroupAdmin;
use App\Models\GroupConfig;

class LineUserService extends BaseService implements BaseInterface
{
    /**
     * 用戶
     *
     * @param mixed $event
     * @param string $trigger // 本次訊息，可用來觸發事件
     * @param array $params // api 參數
     */
    public function __construct($event, $trigger = '', $params = [])
    {
        parent::__construct($event, $trigger, $params);

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
            return $this;
        }

        switch ($this->message) {
            case config('linebot.review_group_sidekick'):
                // 審核(所有)小幫手的申請，先列出管理的群組
                $this->findGroupByAdmin();
                break;

            default:
                // 預設是不回覆訊息
                break;
        }

        return $this;
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
            return;
        }

        // 回傳格式 JSON
        $this->params['replyMsg'] = $groups->toJson();
        $this->params['--template-confirm'] = true;
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
