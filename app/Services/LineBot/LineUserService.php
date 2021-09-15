<?php

namespace App\Services\LineBot;

use App\Models\GroupAdmin;
use App\Models\GroupConfig;

class LineUserService extends BaseService implements BaseInterface
{
    const OPTION_FIND_GROUP = 'find_group';

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

        $this->userId = $event['source']['userId'] ?? null;
    }

    /**
     * handle events
     *
     * @return void
     */
    public function run()
    {
        if ($this->trigger == '' || $this->userId == null) {
            return;
        }

        if ($this->eventType == LineBotService::EVENT_MESSAGE) {
            switch ($this->trigger) {
                case 'list':
                    $options = $this->getOptions();
                    (new LineReplyService())
                        ->setButtonList($options)
                        ->send($this->params['replyToken']);
                    break;

                default:
                    // 預設是不回覆訊息
                    break;
            }

            return;
        }

        if ($this->eventType == LineBotService::EVENT_POSTBACK) {
            switch ($this->trigger) {
                case static::OPTION_FIND_GROUP:
                    // 審核(所有)小幫手的申請，先列出管理的群組
                    $this->findGroupByAdmin();
                    break;

                default:
                    // 預設是不回覆訊息
                    break;
            }

            return;
        }
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

        // 回傳格式
        $input = [];

        foreach ($groups as $group) {
            $input[] = [
                'label' => $group->name,
                'data'  => "id={$group->id}",
                'text'  => $group->name,
                'img'   => $group->picture_url,
            ];
        }

        (new LineReplyService())
            ->setImageCarousel($input)
            ->send($this->params['replyToken']);
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
