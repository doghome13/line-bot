<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Services\LineBot\LineBotService;
use Exception;
use Illuminate\Console\Command;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

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
                            {--template-confirm : Confirm 樣板}
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

            // 篩選條件
            $option = '';

            foreach ($this->options() as $key => $value) {
                if ($key == 'no-specific') {
                    // do nothing
                } elseif ($value) {
                    $option = $key;
                    break;
                }
            }

            switch ($option) {
                case 'silent-on':
                case 'silent-off':
                    // 對於靜音模式，回應貼圖
                    $packageId = 6632;
                    $stickerId = $this->option('silent-on') ? 11825375 : 11825376;
                    $textMessageBuilder = new StickerMessageBuilder($packageId, $stickerId);
                    break;

                case 'template-confirm':
                    $textMessageBuilder = $this->buildConfirmTemplate();
                    break;

                default:
                    $textMessageBuilder = new TextMessageBuilder($this->randomMsg());
                    break;
            }

            // send
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

    /**
     * Confirm 樣板
     *
     * @return MessageBuilder
     */
    private function buildConfirmTemplate()
    {
        $data = json_decode($this->argument('replyMsg'));
        $userTemplate = [];

        foreach ($data as $user) {
            // 先建立 actions
            // $action = new MessageTemplateActionBuilder($user->name, $user->id);
            $data = "id={$user->id}";
            $action = new PostbackTemplateActionBuilder($user->name, $data, $user->name);

            // ImageCarouselColumnTemplateBuilder
            $userTemplate[] = new ImageCarouselColumnTemplateBuilder($user->picture_url, $action);
        }

        // ImageCarouselTemplateBuilder
        $columnTemplate = new ImageCarouselTemplateBuilder($userTemplate);

        return new TemplateMessageBuilder('本裝置不能使用', $columnTemplate);
    }
}
