<?php

if (!function_exists('set_log')) {
    function set_log($content, $code = null)
    {
        try {
            $log = new \App\Models\DevLogs();
            $log->code = $code;
            $log->msg = $content;
            $log->save();

            return 'OK';
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
