<?php

namespace App\Services\LineBot;

class LineBotService
{
    // type of webhook event
    const EVENT_MESSAGE       = 'message';
    const EVENT_MEMBER_JOINED = 'memberJoined';
    const EVENT_MEMBER_LEFT   = 'memberLeft';
    const EVENT_JOIN          = 'join'; // bot 加入群組
    const EVENT_POSTBACK      = 'postback';

    const SOURCE_TYPE_GROUP = 'group';
    const SOURCE_TYPE_USER  = 'user';

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

    public function run()
    {
        foreach ($this->events as $event) {
            $type = $event['type'] ?? '';

            switch ($type) {
                case static::EVENT_MESSAGE:
                    $this->eventMessage($event);
                    break;

                case static::EVENT_JOIN:
                    // 第一次加入群組
                    (new LineGroupService($event, config('linebot.update_group')))->run();
                    break;

                case static::EVENT_MEMBER_LEFT:
                    // 會員離開群組
                    (new LineGroupService($event))->removeAdmin();
                    break;

                case static::EVENT_POSTBACK:
                    $this->eventPostback($event);
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

        $sourceType = $event['source']['type'] ?? '';
        $text       = trim($message['text']);

        // 會得到 replyToken, message
        $params = [
            'replyToken' => $event['replyToken'],
            'replyMsg'   => $text,
        ];

        switch ($sourceType) {
            case static::SOURCE_TYPE_GROUP:
                (new LineGroupService($event, $text, $params))->run();
                break;

            case static::SOURCE_TYPE_USER:
                (new LineUserService($event, $text, $params))->run();
                break;

            default:
                # code...
                break;
        }
    }

    private function eventPostback($event)
    {
        $sourceType   = $event['source']['type'] ?? '';
        $data         = $event['postback']['data'];
        list($option) = LineReplyService::decodeData($data);

        set_log($option);

        // 會得到 replyToken, message
        $params = [
            'replyToken' => $event['replyToken'],
            'data'       => $event['postback']['data'],
        ];

        switch ($sourceType) {
            case static::SOURCE_TYPE_GROUP:
                (new LineGroupService($event, $option, $params))->run();
                break;

            case static::SOURCE_TYPE_USER:
                (new LineUserService($event, $option, $params))->run();
                break;

            default:
                # code...
                break;
        }
    }
}
