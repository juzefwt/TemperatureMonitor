<?php

use Symfony\Component\HttpFoundation\Request;
use Winkiel\DatePeriod;
use Winkiel\EnergyCalculator;
use Winkiel\Statistics;

require_once __DIR__.'/../vendor/autoload.php'; 

$app = new Silex\Application(); 
$app['debug'] = true;
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname'     => 'winkiel',
        'user' => 'root',
        'password' => 'PLUGQ91I',
//         'dbname'     => 'winkiel',
//         'user' => 'winkiel',
//         'password' => 'weatherdataandstuff',
    ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$twig = $app['twig'];
$twig->addExtension(new \Entea\Twig\Extension\AssetExtension($app));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->get('/', function() use ($app) {
    $query = 'SELECT s.uid as uid, s.name as name, m.value as value
            FROM (SELECT * FROM measures ORDER BY timestamp DESC LIMIT 20) as m INNER JOIN sensors s ON (m.sensor_id = s.id) 
            GROUP BY m.sensor_id ORDER BY s.name LIMIT 5';
    $currentMeasures = $app['db']->fetchAll($query);

    foreach ($currentMeasures as $item)
    {
        $data[$item['uid']] = array(
            'name' => $item['name'],
            'value' => $item['value'],
            'min' => 0,
            'max' => 0,
            'avg' => 0,
        );
    }

    $functions = array('min', 'max', 'avg');
    $functionQuery = "SELECT %s(m.value) as value, s.uid as uid 
        FROM measures m 
        INNER JOIN sensors s ON (m.sensor_id = s.id) 
        WHERE UNIX_TIMESTAMP(timestamp) > ? GROUP BY m.sensor_id ORDER BY m.timestamp DESC";

    foreach ($functions as $f)
    {
        $query = sprintf($functionQuery, $f);
        $stats = $app['db']->fetchAll($query, array(strtotime('today')));

        foreach ($stats as $row)
        {
            $data[$row['uid']][$f] = round($row['value'], 2);
        }
    }

    return $app['twig']->render('index.html.twig', array(
        'data' => $data,
    ));
})
->bind('homepage'); 


$app->get('/data', function(Request $request) use ($app) {

    $uid = $request->get('uid');

    $measures = array();
    $query = '
        SELECT *
        FROM (
            SELECT
                @row := @row +1 AS rownum, m.timestamp as timestamp, m.value as value
            FROM (
                SELECT @row :=0) r, measures m INNER JOIN sensors s ON (m.sensor_id = s.id) WHERE s.uid LIKE ?
            ) ranked
        WHERE
            rownum % 5 = 1
            AND UNIX_TIMESTAMP(timestamp) > ?
    ';
    $data = $app['db']->fetchAll($query, array($uid, strtotime('today')));

    foreach ($data as $row) {
        $measures[] = array(strtotime($row['timestamp']." +1 hours")*1000, (double) $row['value']);
    }

    return $request->get('callback')."(".json_encode($measures).");";
});

$app->get('/prognoza', function(Request $request) use ($app) {
    return $app['twig']->render('forecast.html.twig');
})
->bind('forecast');

$app->get('/koszty', function(Request $request) use ($app) {

    $startDate = new \DateTime(date('Y-m-01', strtotime('today')));
    $endDate = new \DateTime('today');
    $period = DatePeriod::createDatesRange($startDate, $endDate);

    $data = array();
    foreach ($period->getDates() as $date)
    {
        $avgIn = EnergyCalculator::getDailyAvg($app, 'in', $date);
        $avgOut = EnergyCalculator::getDailyAvg($app, 'out', $date);
        $energy = EnergyCalculator::calculateEnergyLoss($avgIn, $avgOut, 24);
        $coal = EnergyCalculator::calculateCoalConsumption($energy);

        $data[] = array(
            'date' => $date->format('d-m-Y'),
            'avg_in' => number_format($avgIn, 2),
            'avg_out' => number_format($avgOut, 2),
            'energy' => number_format($energy, 2),
            'coal' => number_format($coal, 2),
            'cost' => number_format(EnergyCalculator::calculateCoalCost($coal), 2),

        );
    }

    return $app['twig']->render('cost.html.twig', array(
        'data' => $data,
    ));
})
->bind('cost');


$app->post('/measure', function(Request $request) use ($app) { 
    $rawData = trim($request->request->get('data'), '"');
    $rawData = str_replace(array("\\n", "\\"), "", $rawData);
    $measures = json_decode($rawData, true);

    foreach ($measures as $sensor => $value) {
        $sensorId = $app['db']->fetchAssoc('SELECT id FROM sensors WHERE code = ?', array($sensor));
        if (empty($sensorId)) {
            $app['db']->insert('sensors', array('code' => $sensor));
        }
    }

    foreach ($measures as $sensor => $value) {
        $sensor = $app['db']->fetchAssoc('SELECT id FROM sensors WHERE code = ?', array($sensor));
        $app['db']->insert('measures', array('sensor_id' => $sensor['id'], 'value' => $value));
    }

    return "OK";
});

$app->get('/podsumowanie', function(Request $request) use ($app) {

    $stats = new Statistics($app['db']);
    $overallMinOutdoor = $stats->getOverallTemperature('MIN', 'out');
    $overallMaxOutdoor = $stats->getOverallTemperature('MAX', 'out');
    $overallAvgOutdoor = $stats->getOverallTemperature('AVG', 'out');
    $overallMinIndoor = $stats->getOverallTemperature('MIN', 'in');
    $overallMaxIndoor = $stats->getOverallTemperature('MAX', 'in');
    $overallAvgIndoor = $stats->getOverallTemperature('AVG', 'in');

    return $app['twig']->render('resume.html.twig', array(
        'overallMinOutdoor' => $overallMinOutdoor,
        'overallMaxOutdoor' => $overallMaxOutdoor,
        'overallAvgOutdoor' => $overallAvgOutdoor,
        'overallMinIndoor' => $overallMinIndoor,
        'overallMaxIndoor' => $overallMaxIndoor,
        'overallAvgIndoor' => $overallAvgIndoor,
    ));
})
->bind('resume');

$app->get('/resume_chart_data', function(Request $request) use ($app) {

    $query = '
        SELECT
            uid,
            date_format( timestamp, \'%Y-%m-%d\' ) AS moment,
            AVG(value) AS avg
        FROM `archive` 
        INNER JOIN sensors ON ( archive.sensor_id = sensors.id ) 
        WHERE uid IN (?, ?)
        GROUP BY uid, moment
        UNION
        SELECT
            uid,
            date_format( timestamp, \'%Y-%m-%d\' ) AS moment,
            AVG(value) AS avg
        FROM `measures` 
        INNER JOIN sensors ON (measures.sensor_id = sensors.id ) 
        WHERE uid IN (?, ?)
        GROUP BY uid, moment
    ';
    $data = $app['db']->fetchAll($query, array('in', 'out', 'in', 'out'));

    $stuff = array();
    foreach ($data as $row) {
        $stuff[$row['uid']][] = array(
            $row['moment'],
            round((double) $row['avg'], 2)
        );
        $stuff['dates'][] = $row['moment'];
    }
    $stuff['dates'] = array_unique($stuff['dates']);

    return $request->get('callback')."(".json_encode($stuff).");";
});


$app->run(); 
