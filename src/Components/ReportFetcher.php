<?php

declare(strict_types=1);

namespace App\Components;

use App\Config\Constant;
use ClickHouseDB\Client;
use DateTime;

class ReportFetcher
{
    private const DAY = 3600 * 24;
    private const N_DAYS = 14;

    private $db;
    private $query = '
        SELECT
            COUNT(DISTINCT user_id) as count
        FROM
            user_visits
        WHERE user_id IN (
            SELECT user_id FROM user_visits WHERE `timestamp` < %u
        )
        AND user_id not in (
            SELECT user_id FROM user_visits WHERE `timestamp` >= %u AND `timestamp` < %u
        )
        AND `timestamp` >= %u AND `timestamp` < %u;
    ';

    public function __construct()
    {
        $this->db = new Client(Constant::DB_CONNECTION);
        $this->db->database(Constant::DB_NAME);
    }

    public function getCount(DateTime $date): int
    {
        $ts = $date->getTimestamp();

        $query = sprintf(
            $this->query,
            $ts - self::N_DAYS * self::DAY,
            $ts - self::N_DAYS * self::DAY,
            $ts,
            $ts,
            $ts + self::DAY
        );

        $result = $this->db->select($query)->fetchOne();

        return (int) $result['count'];
    }
}