<?php

use Symfony\Component\HttpFoundation\Request;

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
    $query = 'SELECT m.timestamp as timestamp, m.value as value
            FROM measures m INNER JOIN sensors s ON (m.sensor_id = s.id) 
            WHERE s.uid LIKE ? AND UNIX_TIMESTAMP(timestamp) > ?';
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


$app->run(); 
