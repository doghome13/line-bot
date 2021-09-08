<?php

namespace App\Services\LineBot;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineBotService
{
    /**
     * line bot facade
     *
     * @var LINEBot
     */
    public $bot;

    public function __construct()
    {
        $httpClient = new CurlHTTPClient(config('service.line-bot.token'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => config('service.line-bot.secret')]);
    }
}