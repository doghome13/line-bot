<?php

namespace App\Services\LineBot;

use App\Models\GroupAdmin;
use App\Models\GroupConfig;

class LineUserService extends LineBaseService implements LineBaseInterface
{
    // 通用
    const OPTION_COMMON_FIND_GROUP = 'find_group';

    // 管理者
    const OPTION_ADMIN_REVIEW_SIDEKICK = 'review_sidekick';
    const OPTION_ADMIN_REVIEW_CONFIRM  = 'review_sidekick_confirm';
    const OPTION_ADMIN_REVIEW_CANCEL   = 'review_sidekick_cancal';

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
                case config('linebot.operation_list'):
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
                case static::OPTION_COMMON_FIND_GROUP:
                    // 審核(所有)小幫手的申請，先列出管理的群組
                    $this->findGroupByAdmin();
                    break;

                case static::OPTION_ADMIN_REVIEW_SIDEKICK:
                    $this->reviewSidekickApply();
                    break;

                case static::OPTION_ADMIN_REVIEW_CONFIRM:
                case static::OPTION_ADMIN_REVIEW_CANCEL:
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
            $data = [
                'id'                               => $group->id,
                LineReplyService::POSTBACK_TRIGGER => static::OPTION_ADMIN_REVIEW_SIDEKICK,
            ];
            $input[] = [
                'label' => $group->name,
                'data'  => LineReplyService::encodeData($data),
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
        // POSTBACK 回來的資料
        $data = $this->params['data'];

        $group     = GroupConfig::find($data['id']);
        $sidekicks = GroupAdmin::where('is_sidekick', true)
            ->where('applied', true)
            ->where('group_id', $group->id)
            ->get();

        if ($sidekicks->count() == 0) {
            return;
        }

        // 回傳格式
        $input = [];

        foreach ($sidekicks as $sidekick) {
            // 這邊及時拉個人資訊
            $response = LineReplyService::getBot()->getProfile($sidekick->user_id);
            $profile  = $response->isSucceeded ? $response->getJSONDecodedBody() : null;

            $input[] = [
                'img'     => $profile ? $profile['pictureUrl'] : 'Blocked User',
                'label'   => $profile ? $profile['displayName'] : 'Blocked User',
                'text'    => $group->name,
                'actions' => [
                    LineReplyService::POSTBACK_CONFIRM => static::OPTION_ADMIN_REVIEW_CONFIRM,
                    LineReplyService::POSTBACK_CANCEL  => static::OPTION_ADMIN_REVIEW_CANCEL,
                ],
                'data'    => [
                    'id' => $sidekick->id,
                ],
            ];
        }

        (new LineReplyService())
            ->setCarousel($input)
            ->send($this->params['replyToken']);
    }
}
