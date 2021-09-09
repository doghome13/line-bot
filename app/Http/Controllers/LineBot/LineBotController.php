<?php

namespace App\Http\Controllers\LineBot;

use App\Models\DevLogs;
use App\Http\Controllers\Controller;
use Exception;

class LineBotController extends Controller
{
    /**
     * 測試通道
     *
     * @return string
     */
    public function echo()
    {
        // /dev/logs 觀測結果
        try {
            $log = new DevLogs();
            $log->msg = request()->all();
            $log->save();

            return 'OK';
        } catch (Exception $e) {
            $log = new DevLogs();
            $log->code = $e->getCode();
            $log->msg = "LINE: ".$e->getLine().", MSG: ".$e->getMessage();
            $log->save();

            return $e->getMessage();
        }
    }
}