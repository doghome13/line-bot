<?php

namespace App\Services\LineBot;

use App\Models\GroupAdmin;
use App\Models\GroupConfig;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;

class LineBaseService
{
    protected $event;
    protected $trigger;

    /**
     * api 參數
     *
     * @var array
     */
    protected $params;

    /**
     * type of Webhook Event
     *
     * @var string
     */
    protected $eventType;

    /**
     * 群組事件
     *
     * @param mixed $event
     * @param string $trigger // 可用來觸發事件
     * @param array $params // api 參數
     */
    public function __construct($event, string $trigger, array $params)
    {
        $this->event     = $event;
        $this->trigger   = $trigger ?? '';
        $this->params    = $params ?? [];
        $this->eventType = '';

        if (isset($this->event['type'])) {
            $this->eventType = $this->event['type'];
        }
    }

    /**
     * Command LineBotReply
     *
     * @param array $options command 參數
     * @param string $command 指令
     */
    public function reply(array $options, $command = 'line:bot:reply')
    {
        Artisan::call($command, $options);
    }

    /**
     * get options
     *
     * @param string $filter 指定篩選條件
     * @return array
     */
    public function getOptions($filter = '')
    {
        $reflectionClass = new ReflectionClass(static::class);
        $options         = [];

        foreach ($reflectionClass->getConstants() as $key => $option) {
            $pass = $filter != ''
            ? (starts_with($key, "OPTION_{$filter}") || starts_with($key, "OPTION_COMMON_"))
            : starts_with($key, "OPTION_COMMON_");

            if ($pass) {
                $options[] = [
                    'label' => trans("linebot.button.{$option}"),
                    'data'  => LineReplyService::POSTBACK_TRIGGER . "={$option}",
                    'text'  => trans("linebot.button.{$option}"),
                ];
            }
        }

        return $options;
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
     * 檢查管理員身分
     *
     * @param string $userId
     * @param integer $groupId // group_config.id
     * @return bool
     */
    public static function isAdmin(string $userId, int $groupId)
    {
        $admin = GroupAdmin::selectRaw('COUNT(1) AS count')
            ->where('user_id', $userId)
            ->where('is_sidekick', false)
            ->where('group_id', $groupId)
            ->first();

        return $admin->count == 1;
    }

    /**
     * fetch profile
     *
     * @param string $userId
     * @return object
     */
    public static function getProfile($userId)
    {
        $url = "https://api.line.me/v2/bot/profile/{$userId}";

        return LineReplyService::curl($url, '', false, static::class, true);
    }
}
