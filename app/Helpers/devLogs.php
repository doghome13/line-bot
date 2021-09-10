<?php

if (!function_exists('set_log')) {
    function set_log($content, $code = null)
    {
        try {
            (new \App\Services\Dev\LogService())::add($content, $code);

            return 'OK';
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
