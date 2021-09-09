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
    public function getBot()
    {
        $httpClient = new CurlHTTPClient(config('service.line-bot.token'));
        return new LINEBot($httpClient, ['channelSecret' => config('service.line-bot.secret')]);
    }

    public function run()
    {
        foreach ($this->events as $event) {
            $type = $event->type ?? '';

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
        // 目前只處理文字
        if ($event->message->type != static::MSG_TYPE_TEXT) {
            return;
        }

        // 會得到 replyToken, message
        $options = [
            'replyToken' => $event->replyToken,
            'replyMsg'   => $event->message->text . " 啾咪",
        ];
        Artisan::call('line:bot:reply', $options);
    }
}
