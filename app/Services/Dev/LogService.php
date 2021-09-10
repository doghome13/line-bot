<?php

namespace App\Services\Dev;

use App\Models\DevLogs;
use Exception;
use Carbon\Carbon;

class LogService
{
    /**
     * 限制 log 筆數 (因為 heroku 有容量限制)
     */
    const LOG_LIMIT = 10;

    /**
     * add a log
     *
     * @param mixed $content
     * @param mixed $code
     * @return void
     */
    public static function add($content, $code = null)
    {
        try {
            // 只保留今天的紀錄
            $today = Carbon::today()->format('Y-m-d');
            $findYesterday = DevLogs::selectRaw('COUNT(1) as count')
                ->whereDate('created_at', '<', $today)
                ->first();

            if ($findYesterday->count) {
                DevLogs::whereDate('created_at', '<', $today)->delete();
            }

            // 因為 heroku 有容量限制，故限制 log 的筆數
            $logs = DevLogs::selectRaw('COUNT(1) as count')->first();

            if ($logs->count >= static::LOG_LIMIT) {
                DevLogs::first()->delete();
            }

            $log = new DevLogs();
            $log->code = $code;
            $log->msg = $content;
            $log->save();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
