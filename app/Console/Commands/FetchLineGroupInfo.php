<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Services\LineBot\LineBotService;
use App\Services\LineBot\LineGroupService;
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
            $token   = $this->argument('replyToken') ?? '';

            $url = "https://api.line.me/v2/bot/group/{$groupId}/summary";
            $res = LineBotService::curl($url, '', false, static::class);

            // 更新資訊
            $groupConfig              = LineGroupService::groupConfig($groupId);
            $groupConfig->name        = $res->groupName;
            $groupConfig->picture_url = $res->pictureUrl;
            $groupConfig->save();

            if ($token != '') {
                $options = [
                    'replyToken'    => $token,
                    'replyMsg'      => $this->argument('msg') ?? '朕來了',
                    '--no-specific' => true,
                ];
                $this->call('line:bot:reply', $options);
            }
        } catch (Exception $e) {
            if ($token != '') {
                $options = [
                    'replyToken'    => $token,
                    'replyMsg'      => '找不到資料，這穴壞了',
                    '--no-specific' => true,
                ];
                $this->call('line:bot:reply', $options);
            }
            event(new ThrowException($e));
        }
    }
}
