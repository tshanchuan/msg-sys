<?php
/**
 * 建立websocket协议
 *
 */
 use \Workerman\Worker;
 use \GatewayWorker\Gateway;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
// ===更改gateway进程端口，这里改为8282===
$gateway = new Gateway("websocket://0.0.0.0:8484");
// ===更改名称，方便status时查看===
$gateway->name = 'ChatGateway2';
$gateway->count = 4;
$gateway->lanIp = '127.0.0.1';
// ===更改注册服务地址，端口由1236改为1237===
$gateway->registerAddress = '127.0.0.1:1238';
// ===更改内部通讯起始端口，这里改为4000===
$gateway->startPort = 4000;

// $gateway->pingInterval = 10;
// $gateway->pingData = '{"type":"ping"}';
