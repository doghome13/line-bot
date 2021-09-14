<?php

namespace App\Services\LineBot;

use Artisan;
use Exception;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineBotService
{
    // type of webhook event
    const EVENT_MESSAGE       = 'message';
    const EVENT_MEMBER_JOINED = 'memberJoined';
    const EVENT_MEMBER_LEFT   = 'memberLeft';
    const EVENT_JOIN          = 'join'; // bot 加入群組
    const EVENT_POSTBACK      = 'postback';

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

                case static::EVENT_JOIN:
                    // 加入群組
                    $options = [
                        'groupId'    => $event['source']['groupId'],
                        'replyToken' => $event['replyToken'],
                    ];
                    Artisan::call('line:group:info', $options);
                    break;

                case static::EVENT_MEMBER_LEFT:
                    // 會員離開群組
                    (new LineGroupService($event))->removeAdmin();
                    break;

                case static::EVENT_POSTBACK:
                    set_log($event);
                    break;

                default:
                    # code...
                    break;
            }
        }
    }

    /**
     * build curl
     *
     * @param string $url // api path
     * @param mixed $content
     * @param bool $isPost // api method
     * @param string $class
     * @return object
     */
    public static function curl(string $url, $content, $isPost = true, $className = '')
    {
        $curlHeader = [
            'Content-Type:application/json',
            'Authorization: Bearer ' . config('services.linebot.token'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($isPost) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $result      = curl_exec($ch);
        $response    = json_decode($result) ?? null;
        $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 報錯則不處理
        if ($response === null || $reponseCode !== 200) {
            $errormsg = [
                'curl error: ' . $reponseCode,
                'class: ' . $className,
                'api: ' . $url,
                'msg: ' . ($response ? $response->message : ''),
                'content: ' . json_encode($content),
                'result: ' . $result ?? 'null',
            ];
            throw new Exception(implode(', ', $errormsg));
        }

        return $response;
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

        // 來自群組的訊息
        if ($event['source']['type'] == static::SOURCE_TYPE_GROUP) {
            $groupService = new LineGroupService($event, $message['text'], $options);
            $options      = $groupService->run()->options;
        } else {
            // 是否為個人用戶的訊息
            $userService = new LineUserService($event, $message['text'], $options);
            $options     = $userService->run()->options;
        }

        // 預設不回覆
        // 群組訊息則是有條件未符合、靜音模式
        if ($options == null) {
            return;
        }

        Artisan::call('line:bot:reply', $options);
    }
}
