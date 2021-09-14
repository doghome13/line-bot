<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Services\LineBot\LineBotService;
use Exception;
use Illuminate\Console\Command;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
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
                            {--silent-on}
                            {--silent-off}
                            {--no-specific : 不使用特定字句}
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
            $bot = LineBotService::getBot();

            // 對於靜音模式，回應貼圖
            if ($this->option('silent-on') || $this->option('silent-off')) {
                $packageId = 6632;
                $stickerId = $this->option('silent-on') ? 11825375 : 11825376;
                $textMessageBuilder = new StickerMessageBuilder($packageId, $stickerId);
            } else {
                $textMessageBuilder = new TextMessageBuilder($this->randomMsg());
            }

            $response = $bot->replyMessage($this->argument('replyToken'), $textMessageBuilder);

            if ($response->isSucceeded()) {
                echo 'done!';
                return;
            }

            // 回傳失敗
            set_log($response->getRawBody(), $response->getHTTPStatus());

        } catch (Exception $e) {
            event(new ThrowException($e));
        }
    }

    /**
     * 隨機回覆
     *
     * @return string
     */
    private function randomMsg()
    {
        $randCount = mt_rand(1, 5);
        $randMsg   = mt_rand(1, 10);
        $msg       = [
            ($randMsg == 1 && !$this->option('no-specific')) ? '老子累了' : $this->argument('replyMsg'),
            ' ',
        ];

        while ($randCount) {
            $msg[] = '喵';
            $randCount--;
        }

        return implode('', $msg);
    }
}
