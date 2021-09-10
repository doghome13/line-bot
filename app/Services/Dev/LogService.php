<?php

namespace App\Services\Dev;

use App\Models\DevLogs;
use App\Models\LogException;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class LogService
{
    /**
     * 限制 log 筆數 (因為 heroku 有容量限制)
     *
     * @var integer
     */
    const LOG_LIMIT = 10;

    /**
     * log 類型
     *
     * @var string
     */
    const LOG_TYPE_DEV = 'dev';
    const LOG_TYPE_EXCEPTION = 'exception';

    /**
     * log
     *
     * @param string $logType // log 類型
     */
    public function __construct($logType = 'dev')
    {
        $this->logType = $logType;
    }

    /**
     * add a log
     *
     * @param mixed $content
     * @param mixed $code // status code OR
     * @param mixed $exception // exception object
     * @return void
     */
    public function add($content, $code = null, $exception = null)
    {
        try {
            // 只保留今天的紀錄
            $today         = Carbon::today()->format('Y-m-d');
            $findYesterday = $this->query()->selectRaw('COUNT(1) as count')
                ->whereDate('created_at', '<', $today)
                ->first();

            if ($findYesterday->count) {
                $this->query()->whereDate('created_at', '<', $today)->delete();
            }

            // 因為 heroku 有容量限制，故限制 log 的筆數
            $logs = $this->query()->selectRaw('COUNT(1) as count')->first();

            if ($logs->count >= static::LOG_LIMIT) {
                $this->query()->first()->delete();
            }

            switch (get_class($logs)) {
                case DevLogs::class:
                    $log       = new DevLogs();
                    $log->code = $code;
                    $log->msg  = $content;
                    $log->save();
                    break;

                case LogException::class:
                    $log             = new LogException();
                    $log->code       = $exception->getCode();
                    $log->class_name = get_class($exception);
                    $log->file       = $exception->getFile();
                    $log->line       = $exception->getLine();
                    $log->url        = request()->url();
                    $log->message    = $content ?: 'unknown error';
                    $log->ip         = request()->ip();
                    $log->save();

                default:
                    # code...
                    break;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * get model
     *
     * @return DevLogs|LogException
     */
    private function query()
    {
        return $this->logType ? DevLogs::query() : LogException::query();
    }
}
