<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php'; 

$app = new Silex\Application(); 
//$app['debug'] = true;
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname'     => 'winkiel',
        'user' => 'winkiel',
        'password' => 'weatherdataandstuff'
    ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/', function() use ($app) {
    $measures = array();
    $query = 'SELECT s.code as code, s.name as name, m.value as value, m.timestamp as timestamp 
            FROM measures m INNER JOIN sensors s ON (m.sensor_id = s.id) 
            ORDER BY m.timestamp DESC LIMIT 50';
    //while ($record = $app['db']->fetchAssoc($query)) {
      //  $measures[] = $record;
    //}
    $measures = $app['db']->fetchAll($query);
//    die(print_r($measures));
    return $app['twig']->render('index.html.twig', array('measures' => $measures));
}); 

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
