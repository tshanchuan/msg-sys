<?php
/**
 * 建立http协议
 *
 */
 use \Workerman\Worker;
 use \GatewayWorker\Gateway;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';

require_once ('Routes.php');
use \Central\Routes;

// 将屏幕打印输出到Worker::$stdoutFile指定的文件中
Worker::$stdoutFile = 'msg_api.log';

$http_gateway = new Gateway("http://0.0.0.0:8181");
$http_gateway->name="http";
$http_gateway->count = 4;
$http_gateway->lanIp = '127.0.0.1';
$http_gateway->startPort = 2800;
$http_gateway->registerAddress = '127.0.0.1:1238';

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
