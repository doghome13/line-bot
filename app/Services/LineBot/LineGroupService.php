<?php

namespace App\Services\LineBot;

use App\Events\ThrowException;
use App\Models\GroupAdmin;
use App\Models\GroupConfig;
use DB;
use Exception;

class LineGroupService extends BaseService implements BaseInterface
{
    /**
     * 群組事件
     *
     * @param mixed $event
     * @param string $trigger // 本次訊息，可用來觸發事件
     * @param array $params // api 參數
     */
    public function __construct($event, $trigger = '', $params = [])
    {
        parent::__construct($event, $trigger, $params);

        // groupId 為必須
        $this->groupId = $event['source']['groupId'] ?? null;

        if ($this->groupId == null) {
            throw new Exception('invalid groupId');
        }
    }

    /**
     * handle events
     *
     * @return void
     */
    public function run()
    {
        if ($this->trigger == '') {
            return;
        }

        switch ($this->trigger) {
            case config('linebot.update_group'):
                // 更新群組資訊
                $options = [
                    'groupId'    => $this->groupId,
                    'replyToken' => $this->event['replyToken'],
                    'msg'        => '朕來了',
                ];

                // 排除自動加入群組的事件
                if ($this->eventType != LineBotService::EVENT_JOIN) {
                    $options['msg']      = '好的';
                    $options['rand-msg'] = true;
                }

                $this->reply($options, 'line:group:info');
                break;

            case config('linebot.claim_group_admin'):
                // 註冊群組的管理者
                $this->registerAdmin();
                break;

            case config('linebot.claim_group_sidekick'):
                // 註冊小幫手
                $this->registerSidekick();
                break;

            case config('linebot.able_apply_group_sidekick'):
                // 申請小幫手的權限
                $this->ableApplySidekick();
                break;

            case config('linebot.silent_on'):
                // 靜音 ON
                $config = $this->groupConfig($this->groupId);

                if ($config == null || $config->silent_mode) {
                    return;
                }

                $config->switchSilent();
                $options = [
                    'replyToken'  => $this->event['replyToken'],
                    'replyMsg'    => '',
                    '--silent-on' => true,
                ];
                $this->reply($options);
                break;

            case config('linebot.silent_off'):
                // 靜音 OFF
                $config = $this->groupConfig($this->groupId);

                if ($config == null || !$config->silent_mode) {
                    return;
                }

                $config->switchSilent();
                $options = [
                    'replyToken'   => $this->event['replyToken'],
                    'replyMsg'     => '',
                    '--silent-off' => true,
                ];
                $this->reply($options);
                break;

            default:
                if ($this->eventType == LineBotService::EVENT_MESSAGE) {
                    $config = $this->groupConfig($this->groupId);

                    if ($config == null || $config->silent_mode) {
                        return;
                    }

                    $options = [
                        'replyToken' => $this->event['replyToken'],
                        'replyMsg'   => $this->trigger,
                        '--specific' => true,
                        '--rand-msg' => true,
                    ];
                    $this->reply($options);
                }
                break;
        }
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
            $find              = new GroupConfig();
            $find->group_id    = $groupId;
            $find->silent_mode = true;
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
            ->where('is_sidekick', false)
            ->first();
    }

    /**
     * 該群組的小幫手
     *
     * @param string $groupId // 群組 id
     * @param mixed $userId // 指定用戶的 id 來檢查身分
     * @return GroupAdmin|null
     */
    public static function groupSidekick(string $groupId, $userId = null)
    {
        $query = GroupAdmin::where('group_id', function ($sub) use ($groupId) {
            return $sub->select('id')
                ->from('group_config')
                ->where('group_id', $groupId);
        })
            ->where('is_sidekick', true);

        if ($userId == null) {
            // applied = false，表示審核通過
            return $query->where('applied', false)->get();
        }

        return $query->select(['id', 'applied'])
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * 移除管理者
     * 當會員離開群組，需自動刪除
     *
     * @return void
     */
    public function removeAdmin()
    {
        try {
            $admin   = $this->groupAdmin($this->groupId);
            $group   = $this->groupConfig($this->groupId);
            $members = $this->event['left']['members'];
            $userIds = [];

            foreach ($members as $member) {
                $userIds[] = $member['userId'];
            }

            // 若管理員離開，則該群組所有小幫手也失去資格
            if ($admin != null && in_array($admin->user_id, $userIds)) {
                GroupAdmin::where('group_id', $group->id)->delete();

                return;
            }

            // 小幫手離開群組
            GroupAdmin::whereIn('user_id', $userIds)
                ->where('group_id', $group->id)
                ->delete();
        } catch (Exception $e) {
            event(new ThrowException($e));
        }
    }

    /**
     * 註冊群組管理者
     *
     * @return void
     */
    private function registerAdmin()
    {
        try {
            $userId = $this->event['source']['userId'];
            $admin  = $this->groupAdmin($this->groupId);

            // 註冊
            if ($admin == null) {
                $group             = $this->groupConfig($this->groupId);
                $find              = new GroupAdmin();
                $find->user_id     = $userId;
                $find->group_id    = $group->id;
                $find->is_sidekick = false;
                // $find->applied_at  = Carbon::now();
                $find->save();

                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => '主人~',
                    '--rand-msg' => true,
                ];
                $this->reply($options);
                return;
            }

            if ($admin != null && $admin->user_id != $userId) {
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => "朕 心有所屬\n退下吧",
                    '--rand-msg' => true,
                ];
                $this->reply($options);
                return;
            }

            // 已是管理者
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => "別來調戲朕",
                '--rand-msg' => true,
            ];
            $this->reply($options);
        } catch (Exception $e) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => "更新管理員失敗\n罐罐不夠多",
                '--rand-msg' => true,
            ];
            $this->reply($options);
            event(new ThrowException($e));
        }
    }

    /**
     * 註冊小幫手
     *
     * @return void
     */
    private function registerSidekick()
    {
        try {
            $userId = $this->event['source']['userId'];

            // 是否申請過
            $check = $this->groupSidekick($this->groupId, $userId);

            if ($check != null) {
                // group_admin.applied 來驗證申請是否通過
                $msg     = $check->applied ? '審核中' : '你這奴才';
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => $msg,
                ];
                $this->reply($options);
                return;
            }

            $group         = $this->groupConfig($this->groupId);
            $countSidekick = GroupAdmin::selectRaw('COUNT(1) AS count')
                ->where('group_id', $group->id)
                ->where('is_sidekick', true)
                ->first();

            // 是否需要小幫手，上限為 3 位
            if (!$group->need_sidekick || $countSidekick->count == 3) {
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => '朕不缺奴才',
                ];
                $this->reply($options);
                return;
            }

            // 未設定主要管理者
            $admin = $this->groupAdmin($this->groupId);

            if ($admin == null) {
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => '我還沒有主人',
                ];
                $this->reply($options);
                return;
            }

            // 避免管理者身分錯亂
            if ($admin->user_id == $userId) {
                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => '大膽奴才',
                ];
                $this->reply($options);
                return;
            }

            $group                 = $this->groupConfig($this->groupId);
            $sidekick              = new GroupAdmin();
            $sidekick->user_id     = $userId;
            $sidekick->group_id    = $group->id;
            $sidekick->is_sidekick = true;
            $sidekick->applied     = true;
            $sidekick->save();

            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => '還不快謝主隆恩',
                '--rand-msg' => true,
            ];
            $this->reply($options);
        } catch (Exception $e) {
            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => "更新管理員失敗\n罐罐不夠多",
                '--rand-msg' => true,
            ];
            $this->reply($options);
            event(new ThrowException($e));
        }
    }

    /**
     * 申請小幫手的權限
     *
     * @return void
     */
    private function ableApplySidekick()
    {
        try {
            $userId = $this->event['source']['userId'];
            $admin  = $this->groupAdmin($this->groupId);

            // 主要管理者才能修改
            if ($admin == null || $admin->user_id != $userId) {
                return;
            }

            // find group_config
            $group = $admin->group;

            // 開放申請
            if (!$group->need_sidekick) {
                $group->need_sidekick = true;
                $group->save();

                $options = [
                    'replyToken' => $this->event['replyToken'],
                    'replyMsg'   => '--以下開放申請奴才--',
                ];
                $this->reply($options);
                return;
            }

            // 關閉申請
            DB::beginTransaction();

            $group->need_sidekick = false;
            $group->save();

            // 其他申請未通過則刪除
            GroupAdmin::where('group_id', $group->id)
                ->where('is_sidekick', true)
                ->where('applied', true)
                ->delete();
            DB::commit();

            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => '--申請奴才截止--',
            ];
            $this->reply($options);
        } catch (Exception $e) {
            DB::rollBack();

            $options = [
                'replyToken' => $this->event['replyToken'],
                'replyMsg'   => "更新群組設定失敗\n罐罐不夠多",
                '--rand-msg' => true,
            ];
            $this->reply($options);

            event(new ThrowException($e));
        }
    }
}
