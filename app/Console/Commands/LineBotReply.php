<?php

namespace App\Console\Commands;

use App\Services\LineBot\LineBotService;
use Exception;
use Illuminate\Console\Command;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineBotReply extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:bot:reply
                            {replyToken : Reply token received via webhook.}
                            {replyMsg : Messages to send.}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'LINE 聊天機器人 - 回覆訊息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // 套用 LINE API SDK
            $bot                = LineBotService::getBot();
            $textMessageBuilder = new TextMessageBuilder($this->randomMsg());
            $response           = $bot->replyMessage($this->argument('replyToken'), $textMessageBuilder);

            if ($response->isSucceeded()) {
                echo 'done!';
                return;
            }

            // 回傳失敗
            set_log($response->getRawBody(), $response->getHTTPStatus());

            // 回傳 message 有一定格式
            // $url  = 'https://api.line.me/v2/bot/message/reply';
            // $data = [
            //     'replyToken' => $this->argument('replyToken'),
            //     'messages'   => $this->argument('replyMsg'),
            //     // 'notificationDisabled' => false,
            // ];
            // $data = http_build_query($data);
            // $this->curl($url, $data);
        } catch (Exception $e) {
            // $this->error('LINE: ' . $e->getLine());
            // $this->error('MSG:' . $e->getMessage());
            throw new Exception($e->getMessage());
        }

        // $this->line('done!');
    }

    // /**
    //  * get curl
    //  *
    //  * @param string $url // api path
    //  * @param mixed $content
    //  * @param bool $isPost // api method
    //  * @return object
    //  */
    // private function curl(string $url, $content, $isPost = true)
    // {
    //     $curlHeader = [
    //         'Content-Type:application/json',
    //         'Authorization: Bearer ' . config('services.linebot.token'),
    //     ];

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HEADER, false);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    //     curl_setopt($ch, CURLOPT_POST, true);

    //     $result      = curl_exec($ch);
    //     $result      = json_decode($result) ?? null;
    //     $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     curl_close($ch);

    //     // 報錯則不處理
    //     if ($result === null || $reponseCode !== 200) {
    //         $errormsg = [
    //             'curl error: ' . $reponseCode,
    //             'class: ' . get_class($this),
    //             'api: ' . $url,
    //             'msg: ' . ($result ? $result->message : ''),
    //             'content: ' . json_encode($content),
    //             'result: ' . $result,
    //         ];
    //         throw new Exception(implode(', ', $errormsg));
    //     }

    //     return $result;
    // }

    /**
     * 隨機回覆
     *
     * @return string
     */
    private function randomMsg()
    {
        $count = rand(1, 5);
        $msg = [
            rand(0, 9) == 1 ? '老子累了' : $this->argument('replyMsg'),
            ' ',
        ];
        while ($count) {
            $msg[] = '喵';
            $count--;
        }

        return implode('', $msg);
    }
}
