<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Services\LineBot\LineReplyService;
use Exception;
use Illuminate\Console\Command;

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
                            {--rand-msg : 句尾加上疊字}
                            {--specific : 使用特定字句}
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
            $service = new LineReplyService();

            if ($this->option('specific')) {
                $service->setSpecific();
            }

            if ($this->option('rand-msg')) {
                $service->setRandMessage();
            }

            if ($this->option('silent-on') || $this->option('silent-off')) {
                // 對於靜音模式，回應貼圖
                $packageId = 6632;
                $stickerId = $this->option('silent-on') ? 11825375 : 11825376;
                $service->setSicker($packageId, $stickerId);
            } else {
                $service->setText($this->argument('replyMsg'));
            }

            $service->send($this->argument('replyToken'));
        } catch (Exception $e) {
            event(new ThrowException($e));
        }
    }
}
