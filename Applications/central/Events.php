<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use Msg\Central\Routes;
use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events {

    /**
     * 当进程启动时触发
     * 每个进程生命周期内都只会触发一次。
     * 可以在这里为每一个businessWorker进程做一些全局初始化工作，例如设置定时器，初始化redis等连接等。
     */
    public static function onWorkerStart($businessWorker) {
       echo "Message start\n";
    }
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        // 向当前client_id发送数据
        //Gateway::sendToClient($client_id, "Hello $client_id");
        // 向所有人发送
        //Gateway::sendToAll("$client_id login");
    }

   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message) {
        // 处理消息 json to array
        if(isset($message['post'])){
          $message['client_id'] = $client_id;
          Routes::requestApi($message);
          return TRUE;
        }
        $message_array = json_decode($message,true);
        if(is_array($message_array)){
          $message_array['client_id'] = $client_id;
          Routes::index($message_array);
          return TRUE;
        }
        //返回错误提示
        Gateway::sendToClient($client_id, '{"ERRORCODE":"ME01000",RESULT:"param type error"}');
        //关闭连接
        Gateway::closeClient($client_id);

   }

   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       // 向所有人发送
      //  GateWay::sendToAll("$client_id logout");
   }
}
