<?php

namespace App\Http\Controllers\LineBot;

use App\Events\ThrowException;
use App\Http\Controllers\Controller;
use App\Services\LineBot\LineBotService;
use Exception;
use Illuminate\Http\Request;

class LineBotController extends Controller
{
    /**
     * 測試通道
     *
     * @return string
     */
    function echo (Request $request) {
        // /dev/logs 觀測結果
        try {
            set_log($request->all());

            return 'OK';
        } catch (Exception $e) {
            $msg = "LINE: " . $e->getLine() . ", MSG: " . $e->getMessage();
            set_log($msg, $e->getCode());

            return $e->getMessage();
        }
    }

    /**
     * webhook
     *
     * @return void
     */
    public function callback(Request $request)
    {
        try {
            (new LineBotService($request->get('events')))->run();

            return 'OK';
        } catch (Exception $e) {
            event(new ThrowException($e));
            return 'ERROR';
        }
    }
}
