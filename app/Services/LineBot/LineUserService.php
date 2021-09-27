<?php

namespace App\Services\LineBot;

use App\Events\ThrowException;
use App\Models\GroupAdmin;
use App\Models\GroupConfig;
use Exception;

class LineUserService extends LineBaseService implements LineBaseInterface
{
    // 通用
    const OPTION_COMMON_FIND_GROUP = 'find_group';

    // 管理者
    const OPTION_ADMIN_REVIEW_SIDEKICK = 'review_sidekick';
    const OPTION_ADMIN_REVIEW_CONFIRM  = 'review_sidekick_confirm';
    const OPTION_ADMIN_REVIEW_CANCEL   = 'review_sidekick_cancal';
    const OPTION_ADMIN_LIST_SIDEKICK   = 'list_sidekick';
    const OPTION_ADMIN_REMOVE_SIDEKICK = 'remove_sidekick';
    const OPTION_ADMIN_UPDATE_SIDEKICK = 'update_sidekick';

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
                    // 先列出管理的群組
                    $this->findGroupByAdmin();
                    break;

                case static::OPTION_ADMIN_REVIEW_SIDEKICK:
                    $this->reviewSidekickApply();
                    break;

                case static::OPTION_ADMIN_REVIEW_CONFIRM:
                    $this->reviewSidekickApproved();
                    break;

                case static::OPTION_ADMIN_REVIEW_CANCEL:
                    $this->reviewSidekickDisapproved();
                    break;

                case static::OPTION_ADMIN_LIST_SIDEKICK:
                    $this->listSidekicks();
                    break;

                case static::OPTION_ADMIN_REMOVE_SIDEKICK:
                    $this->removeSidekick();
                    break;

                case static::OPTION_ADMIN_UPDATE_SIDEKICK:
                    $this->updateSidekick();
                    break;

                default:
                    if (config('app.debug')) {
                        set_log($this->event);
                    }
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
        $admins = GroupAdmin::where('user_id', $this->userId)
            ->where(function ($query) {
                // 管理員 / 審核通過
                return $query->where('is_sidekick', false)
                    ->orWhere('applied', false);
            })
            ->with('group:id,name,picture_url')
            ->get();

        if ($admins->count() == 0) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => trans('linebot.text.empty'),
            ];
            $this->reply($options);
            return;
        }

        // 回傳格式
        $input = [];

        foreach ($admins as $admin) {
            $data = [
                'img'   => $admin->group->picture_url,
                'label' => $admin->group->name,
                'data'  => [
                    'id' => $admin->group_id,
                ],
            ];

            if (!$admin->is_sidekick) {
                $data['text']    = trans('linebot.text.admin-menu');
                $data['actions'] = [
                    LineReplyService::POSTBACK_REVIEW_SIDEKICK => static::OPTION_ADMIN_REVIEW_SIDEKICK,
                    LineReplyService::POSTBACK_LIST_SIDEKICK   => static::OPTION_ADMIN_LIST_SIDEKICK,
                ];
            } else {
                $data['text']    = trans('linebot.text.sidekick-menu');
                $data['actions'] = [
                    // 之後加功能
                ];
            }

            $input[] = $data;
        }

        (new LineReplyService())
            ->setCarousel($input)
            ->send($this->params['replyToken']);
    }

    /**
     * 該用戶所屬的小幫手，審核申請
     *
     * @return void
     */
    private function reviewSidekickApply()
    {
        $data  = $this->params['data']; // POSTBACK 回來的資料
        $group = GroupConfig::find($data['id']);

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $group->id)) {
            return;
        }

        $sidekicks = GroupAdmin::where('is_sidekick', true)
            ->where('applied', true)
            ->where('group_id', $group->id)
            ->get();

        if ($sidekicks->count() == 0) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => trans('linebot.text.empty'),
            ];
            $this->reply($options);
            return;
        }

        // 回傳格式
        $input = [];

        foreach ($sidekicks as $sidekick) {
            $input[] = [
                'img'     => $sidekick->picture_url,
                'label'   => $sidekick->name,
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

    /**
     * 審核小幫手通過
     */
    private function reviewSidekickApproved()
    {
        $data     = $this->params['data']; // POSTBACK 回來的資料
        $sidekick = GroupAdmin::where('id', $data['id'])
            ->where('applied', true)
            ->first();

        if ($sidekick == null) {
            return;
        }

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $sidekick->group_id)) {
            return;
        }

        $sidekick->applied = false;
        $sidekick->save();

        $options = [
            'replyToken' => $this->event['replyToken'],
            'replyMsg'   => trans('linebot.text.done'),
        ];
        $this->reply($options);
    }

    /**
     * 審核小幫手不通過
     */
    private function reviewSidekickDisapproved()
    {
        $data     = $this->params['data']; // POSTBACK 回來的資料
        $sidekick = GroupAdmin::where('id', $data['id'])
            ->where('applied', true)
            ->first();

        if ($sidekick == null) {
            return;
        }

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $sidekick->group_id)) {
            return;
        }

        $sidekick->delete();

        $options = [
            'replyToken' => $this->event['replyToken'],
            'replyMsg'   => trans('linebot.text.done'),
        ];
        $this->reply($options);
    }

    /**
     * 該用戶所屬的小幫手
     *
     * @return void
     */
    private function listSidekicks()
    {
        $data  = $this->params['data']; // POSTBACK 回來的資料
        $group = GroupConfig::find($data['id']);

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $group->id)) {
            return;
        }

        $sidekicks = GroupAdmin::where('is_sidekick', true)
            ->where('applied', false)
            ->where('group_id', $group->id)
            ->get();

        if ($sidekicks->count() == 0) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => trans('linebot.text.empty'),
            ];
            $this->reply($options);
            return;
        }

        // 回傳格式
        $input = [];

        foreach ($sidekicks as $sidekick) {
            $input[] = [
                'img'     => $sidekick->picture_url,
                'label'   => $sidekick->name,
                'text'    => $group->name,
                'actions' => [
                    LineReplyService::POSTBACK_UPDATE_SIDEKICK => static::OPTION_ADMIN_UPDATE_SIDEKICK,
                    LineReplyService::POSTBACK_REMOVE_SIDEKICK => static::OPTION_ADMIN_REMOVE_SIDEKICK,
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

    /**
     * 移除小幫手
     *
     * @return void
     */
    private function removeSidekick()
    {
        $data     = $this->params['data']; // POSTBACK 回來的資料
        $sidekick = GroupAdmin::find($data['id']);

        if ($sidekick == null) {
            return;
        }

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $sidekick->group_id)) {
            return;
        }

        $sidekick->delete();

        $options = [
            'replyToken' => $this->event['replyToken'],
            'replyMsg'   => trans('linebot.text.done'),
        ];
        $this->reply($options);
    }

    /**
     * 更新小幫手資訊
     *
     * @return void
     */
    private function updateSidekick()
    {
        $data     = $this->params['data']; // POSTBACK 回來的資料
        $sidekick = GroupAdmin::find($data['id']);

        if ($sidekick == null) {
            return;
        }

        // 驗證管理員身分
        if (!$this->isAdmin($this->userId, $sidekick->group_id)) {
            return;
        }

        try {
            $profile = $this->getProfile($sidekick->user_id);
            $userId  = $profile->userId ?? null;

            if ($userId != $sidekick->user_id) {
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => trans('linebot.text.blocked'),
                ];
                $this->reply($options);
                return;
            }

            $sidekick->name        = $profile->displayName;
            $sidekick->picture_url = $profile->pictureUrl;
            $sidekick->save();

            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => trans('linebot.text.done'),
            ];
            $this->reply($options);
        } catch (Exception $e) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => trans('linebot.text.update-fail'),
                '--rand-msg' => true,
            ];
            $this->reply($options);
            event(new ThrowException($e));
        }
    }
}
