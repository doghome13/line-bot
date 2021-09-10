<?php

namespace App\Http\Controllers;

use App\Models\DevLogs;
use App\Http\Controllers\Controller;

class DevController extends Controller
{
    public function listOfLogs()
    {
        $logs = DevLogs::orderBy('created_at', 'desc')->get();
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
            print_r($log->msg);
            echo "</pre>";
            $count++;
        }
    }
}