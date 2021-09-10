<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DevLogs;
use App\Models\LogException;
use App\Services\Dev\LogService;

class DevController extends Controller
{
    public function listOfLogs($type = null)
    {
        if ($type == null) {
            $logs = DevLogs::orderBy('created_at', 'desc')->get();
        } else if ($type == LogService::LOG_TYPE_EXCEPTION) {
            $logs = LogException::orderBy('created_at', 'desc')->get();
        } else {
            return;
        }

        $count = 1;

        if ($logs->count() == 0) {
            echo "<pre>";
            echo "EMPTY!";
            echo "</pre>";
            return;
        }

        foreach ($logs as $log) {
            echo "<pre>";
            echo "#{$count}";
            echo "<br/>";
            echo "TIME: {$log->created_at}";
            echo "<br/>";
            echo "CODE: {$log->code}";
            echo "<br/>";

            if ($type == LogService::LOG_TYPE_EXCEPTION) {
                echo "CLASS: {$log->class_name}";
                echo "<br/>";
                echo "FILE: {$log->file}";
                echo "<br/>";
                echo "LINE: {$log->line}";
                echo "<br/>";
                echo "URL: {$log->url}";
                echo "<br/>";
                echo "IP: {$log->ip}";
                echo "<br/>";
                echo "MSG: {$log->message}";
                echo "<br/>";
            } else {
                print_r($log->msg);
            }
            echo "</pre>";

            $count++;
        }
    }
}
