<?php

namespace App\Services\LineBot;

use Exception;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineReplyService
{
    /**
     * 訊息回覆
     *
     * @param array $params // 發送訊息需要的參數
     */
    public function __construct()
    {
        $this->isSilent = false;
        $this->randMsg = false;
        $this->specific = false;
        $this->messageBuilder = [];
    }

    /**
     * 設定為靜音模式
     *
     * @return $this
     */
    public function setSilentMode()
    {
        $this->isSilent = true;

        return $this;
    }

    /**
     * 句尾加上疊字
     *
     * @return $this
     */
    public function setRandMessage()
    {
        $this->randMsg = true;

        return;
    }

    /**
     * 使用特殊字句
     *
     * @return $this
     */
    public function setSpecific()
    {
        $this->specific = true;

        return $this;
    }

    public function send(string $token)
    {
        $bot = static::getBot();

        foreach ($this->messageBuilder as $messageBuilder) {
            $response = $bot->replyMessage($token, $messageBuilder);

            // 回傳失敗
            if (!$response->isSucceeded()) {
                set_log($response->getRawBody(), $response->getHTTPStatus());
            }
        }
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
     * 一般文字
     *
     * @return $this
     */
    public function setText($msg)
    {
        $msg = $this->randomMsg($msg);
        $this->messageBuilder[] = new TextMessageBuilder($msg);

        return $this;
    }

    /**
     * 貼圖
     *
     * @return $this
     */
    public function setSicker($packageId, $stickerId)
    {
        $this->messageBuilder[] = new StickerMessageBuilder($packageId, $stickerId);

        return $this;
    }

    /**
     * 圖片樣板
     *
     * @return $this
     */
    public function setImageCarousel()
    {
        // $data = json_decode($this->argument('replyMsg'));
        // $userTemplate = [];

        // foreach ($data as $user) {
        //     // 先建立 actions
        //     $data = "id={$user->id}";
        //     $action = new PostbackTemplateActionBuilder($user->name, $data, $user->name);

        //     // ImageCarouselColumnTemplateBuilder
        //     $userTemplate[] = new ImageCarouselColumnTemplateBuilder($user->picture_url, $action);
        // }

        // // ImageCarouselTemplateBuilder
        // $columnTemplate = new ImageCarouselTemplateBuilder($userTemplate);

        // return new TemplateMessageBuilder('本裝置不能使用', $columnTemplate);
        return $this;
    }

    /**
     * 隨機回覆
     *
     * @param string $msg
     * @return string
     */
    private function randomMsg(string $msg = '')
    {
        if (!$this->randMsg || $msg == '') {
            return $msg;
        }

        $randCount = mt_rand(1, 5);
        $randMsg   = mt_rand(1, 10);
        $output    = [
            ($randMsg == 1 && $this->specific) ? '老子累了' : $msg,
            ' ',
        ];

        while ($randCount) {
            $output[] = '喵';
            $randCount--;
        }

        return implode('', $output);
    }
}
