<?php

namespace App\Services\LineBot;

use Illuminate\Support\Facades\Artisan;

class BaseService
{
    protected $event;
    protected $trigger;

    /**
     * api 參數
     *
     * @var array
     */
    protected $params;

    /**
     * type of Webhook Event
     *
     * @var string
     */
    protected $eventType;

    /**
     * 群組事件
     *
     * @param mixed $event
     * @param string $trigger // 可用來觸發事件
     * @param array $params // api 參數
     */
    public function __construct($event, string $trigger, array $params)
    {
        $this->event      = $event;
        $this->trigger    = $trigger ?? '';
        $this->params     = $params ?? [];
        $this->eventType  = '';

        if (isset($this->event['type'])) {
            $this->eventType  = $this->event['type'];
        }
    }

    /**
     * Command LineBotReply
     *
     * @param array $options command 參數
     * @param string $command 指令
     */
    public function reply(array $options, $command = 'line:bot:reply')
    {
        Artisan::call($command, $options);
    }
}
