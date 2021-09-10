<?php

namespace App\Services\LineBot;

use App\Models\GroupConfig;
use Artisan;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineBotService
{
    const EVENT_MESSAGE       = 'message';
    const EVENT_MEMBER_JOINED = 'memberJoined';

    const SOURCE_TYPE_GROUP = 'group';

    /**
     * 訊息類型-文字
     *
     * @var string
     */
    const MSG_TYPE_TEXT = 'text';

    public function __construct($events)
    {
        $this->events = $events ?? [];
    }

    /**
     * get line bot
     *
     * @return LINEBot
     */
    public static function getBot()
    {
        $httpClient = new CurlHTTPClient(config('services.linebot.token'));
        return new LINEBot($httpClient, ['channelSecret' => config('services.linebot.secret')]);
    }

    public function run()
    {
        foreach ($this->events as $event) {
            $type = $event['type'] ?? '';

            switch ($type) {
                case static::EVENT_MESSAGE:
                    $this->eventMessage($event);
                    break;

                default:
                    # code...
                    break;
            }
        }
    }

    /**
     * 接受訊息事件
     *
     * @return void
     */
    private function eventMessage($event)
    {
        $message = $event['message'];

        // 目前只處理文字
        if ($message['type'] != static::MSG_TYPE_TEXT) {
            return;
        }

        // 會得到 replyToken, message
        $options = [
            'replyToken' => $event['replyToken'],
            'replyMsg'   => $message['text'],
        ];
        $findGroup = $this->groupConfig($event) ?? null;

        // 群組事件
        if ($findGroup != null) {
            if ($message['text'] == config('services.linebot.silent_off')
                && $findGroup->silent_mode) {
                // 靜音 OFF
                $findGroup->switchSilent();
            } else if ($message['text'] == config('services.linebot.silent_on')
            && !$findGroup->silent_mode) {
                // 靜音 ON
                $findGroup->switchSilent();
            } else if ($findGroup->silent_mode) {
                return;
            }
        }

        Artisan::call('line:bot:reply', $options);
    }

    /**
     * 群組設定
     *
     * @param mixed $event
     * @return GroupConfig|null
     */
    private function groupConfig($event)
    {
        $source = $event['source'] ?? false;

        if (!$source || $source['type'] != static::SOURCE_TYPE_GROUP) {
            return null;
        }

        $find = GroupConfig::where('group_id', $source['userId'])->frist();

        if ($find == null) {
            $find = new GroupConfig();
            $find->group_id = $source['userId'];
            $find->save();
        }

        return $find;
    }
}
