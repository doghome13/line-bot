<?php

namespace App\Services\LineBot;

use Artisan;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineBotService
{
    const EVENT_MESSAGE       = 'message';
    const EVENT_MEMBER_JOINED = 'memberJoined';

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
        Artisan::call('line:bot:reply', $options);
    }
}
