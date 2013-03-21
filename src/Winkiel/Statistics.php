<?php

namespace Winkiel;

class Statistics {

    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getOverallTemperature($function, $sensor)
    {
        $query = '
            SELECT
                '.$function.'(value) AS val
            FROM archive INNER JOIN sensors ON (archive.sensor_id=sensors.id)
            WHERE uid = "'.$sensor.'"';
        $result = $this->db->fetchAll($query);

        return isset($result[0]['val']) ? number_format($result[0]['val'], 2) : 0;
    }
}