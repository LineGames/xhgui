<?php
if (!extension_loaded('xhprof') && !extension_loaded('uprofiler') && !extension_loaded('tideways')) {
    die('xhgui - either extension xhprof, uprofiler or tideways must be loaded');
}

$dir = dirname(__DIR__);
require_once $dir . '/src/Xhgui/Config.php';
Xhgui_Config::load($dir . '/config/config.default.php');
if (file_exists($dir . '/config/config.php')) {
    Xhgui_Config::load($dir . '/config/config.php');
}
unset($dir);

if ((!extension_loaded('mongo') && !extension_loaded('mongodb')) && Xhgui_Config::read('save.handler') === 'mongodb') {
    die('xhgui - extension mongo not loaded');
}

if (!defined('XHGUI_ROOT_DIR')) {
    require dirname(dirname(__FILE__)) . '/src/bootstrap.php';
}

$__SERVER = json_decode($_POST['_SERVER'], true);
$__GET = json_decode($_POST['_GET'], true);
$__ENV = json_decode($_POST['_ENV'], true);

$uri = $__SERVER['REQUEST_URI'];
$time = $__SERVER['REQUEST_TIME'];
$requestTimeFloat = explode('.', $SERVER['REQUEST_TIME_FLOAT']);
if (count($requestTimeFloat) === 1) {
    $requestTimeFloat[1] = 0;
}

$requestTs = new MongoDate($time);
$requestTsMicro = new MongoDate($requestTimeFloat[0], $requestTimeFloat[1]);

$data['_id'] = new MongoId();

$data['meta'] = array(
    'url' => $uri,
    'SERVER' => $__SERVER,
    'get' => $__GET,
    'env' => $__ENV,
    'simple_url' => Xhgui_Util::simpleUrl($uri),
    'request_ts' => $requestTs,
    'request_ts_micro' => $requestTsMicro,
    'request_date' => date('Y-m-d', $time),
);

$data['profile'] = json_decode($_POST['profile']);

try {
    $config = Xhgui_Config::all();
    $config += array('db.options' => array());
    $saver = Xhgui_Saver::factory($config);
    $saver->save($data);

    echo $data['_id'];
} catch (Exception $e) {
}
