<?php

namespace App\Services\LineBot;

use Exception;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class LineReplyService
{
    /**
     * 訊息回覆
     *
     * @param array $params // 發送訊息需要的參數
     */
    public function __construct()
    {
        $this->isSilent       = false;
        $this->randMsg        = false;
        $this->specific       = false;
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

        return $this;
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
        if (empty($this->messageBuilder)) {
            return;
        }

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
     * @param string $msg
     * @return $this
     */
    public function setText($msg)
    {
        if ($msg == '') {
            return $this;
        }

        $msg                    = $this->randomMsg($msg);
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
     * 按鈕樣板
     *
     * @param array $options
     * @return $this
     */
    public function setButtonList(array $options)
    {
        $buttonActions = [];

        foreach ($options as $option) {
            // 先建立 actions // PostbackTemplateActionBuilder
            $data = "option={$option}";
            $label = trans("linebot.button.{$option}");
            $buttonActions[] = new PostbackTemplateActionBuilder($label, $data, $label);
        }

        // ButtonTemplateBuilder
        $template = new ButtonTemplateBuilder(
            '今晚你想來點什麼',
            '除了後空翻',
            null,
            $buttonActions
        );

        // TemplateMessageBuilder
        $this->messageBuilder[] = new TemplateMessageBuilder('本裝置不能使用', $template);

        return $this;
    }

    /**
     * 隨機回覆
     *
     * @param string $msg
     * @return string
     */
    private function randomMsg(string $msg)
    {
        $randMsg = $this->specific ? mt_rand(1, 10) : 0;
        $randMsg = $randMsg == 1 ? '老子累了' : $msg;

        if (!$this->randMsg) {
            return $randMsg;
        }

        $randCount = mt_rand(1, 5);
        $output    = [
            $randMsg,
            ' ',
        ];

        while ($randCount) {
            $output[] = '喵';
            $randCount--;
        }

        return implode('', $output);
    }

    /**
     * build postback data
     *
     * @return string
     */
    public static function encodeData(array $params)
    {
        $output = [];

        foreach ($params as $key => $param)
        {
            $output[] = "{$key}={$param}";
        }

        return implode('&', $output);
    }

    public static function decodeData(string $params)
    {
        $output = [];
        $params = explode('&', $params);
        $params = is_array($params) ? $params : [$params];

        foreach ($params as $param) {
            $split = explode('=', $param);
            $output[$split[0]] = $split[1];
        }

        return $output;
    }
}
