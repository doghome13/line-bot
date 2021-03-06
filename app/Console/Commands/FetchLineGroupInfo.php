<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Services\LineBot\LineBaseService;
use App\Services\LineBot\LineReplyService;
use Exception;
use Illuminate\Console\Command;

class FetchLineGroupInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:group:info
                            {groupId : 群組 id}
                            {replyToken? : 回覆需要帶 token}
                            {msg? : 自訂回覆的訊息}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '撈取 LINE 群組資訊';

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
            $groupId = $this->argument('groupId');

            $url = "https://api.line.me/v2/bot/group/{$groupId}/summary";
            $res = LineReplyService::curl($url, '', false, static::class);

            // 更新資訊
            $groupConfig              = LineBaseService::groupConfig($groupId);
            $groupConfig->name        = $res->groupName;
            $groupConfig->picture_url = $res->pictureUrl;
            $groupConfig->save();

            if ($this->hasArgument('replyToken') && $this->hasArgument('msg')) {
                (new LineReplyService())
                    ->setRandMessage()
                    ->setText($this->argument('msg'))
                    ->send($this->argument('replyToken'));
            }
        } catch (Exception $e) {
            if ($this->hasArgument('replyToken')) {
                (new LineReplyService())
                    ->setRandMessage()
                    ->setText("找不到資料\n這穴壞了")
                    ->send($this->argument('replyToken'));
            }
            event(new ThrowException($e));
        }
    }
}
