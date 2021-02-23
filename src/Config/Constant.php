<?php

declare(strict_types=1);

namespace App\Config;

class Constant
{
    const DB_NAME = 'phptest';
    const DB_CONNECTION = [
        'host' => 'clickhouse.smapps.net',
        'port' => '8123',
        'username' => 'vasilenko',
        'password' => '066c725d191e43759a381a39edd693c2',
    ];
}
