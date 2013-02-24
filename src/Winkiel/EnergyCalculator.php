<?php

namespace Winkiel;

class EnergyCalculator {

    const THERMAL_INSULATION = 100; // W/K
    const ENERGY_COST = 0.3; // zł

    public static function getDailyAvg($app, $sensorUid, $startDate, $endDate)
    {
        $query = '
          SELECT
            AVG(m.value) as avg, UNIX_TIMESTAMP(m.timestamp) as timestamp 
          FROM 
            `measures` m 
          INNER JOIN 
            sensors s ON (m.sensor_id=s.id)
          WHERE 
            s.uid = ?
            AND m.timestamp > ?
            AND m.timestamp < ?
        ';

        $raw = $app['db']->fetchAll($query, array($sensorUid, $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));
        $data = array();

        foreach ($raw as $item)
        {
            $data[date('Y-m-d', $item['timestamp'])] = $item['avg'];
        }

        return $data;
    }

    public static function calculateEnergyLoss($indoorTemp, $outdoorTemp, $periodInHours)
    {
        return self::THERMAL_INSULATION * ($indoorTemp-$outdoorTemp) * $periodInHours * 0.001;
    }

    public static function calculateEnergyCost($energyAmount)
    {
        return $energyAmount * self::ENERGY_COST;
    }
}