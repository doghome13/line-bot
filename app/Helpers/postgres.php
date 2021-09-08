<?php

if (!function_exists('get_db_config')) {
    /**
     * 取得 psotgres database url
     *
     * @return string
     */
    function get_db_config()
    {
        $data = parse_url(getenv('DB_HOST'));

        return [
            'driver'   => 'pgsql',
            'host'     => $data["host"],
            'port'     => $data["port"],
            'database' => ltrim($data["path"], "/"),
            'username' => $data["user"],
            'password' => $data["pass"],
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'require',
        ];
    }
}
