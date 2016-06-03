<?php namespace Msg\Central;
/**
 *	中心路由
 *	@param opt client_id accountID lon lat timestamp
 *  @return
 */

require_once('Controller.php');
require_once('SendMsg.php');
require_once('MsgApi.php');

use \GatewayWorker\Lib\Gateway;

class Routes {

   /**
    *
    * 根据心跳信息进行业务处理
    * @param message
    * @return
    */
   public static $path_array = [
     'connect'  => ['Controller','onConnect'],
     'heart'    => ['Controller', 'onHeart'],
     'offline'  => ['Controller', 'offline'],
     'single_msg'=> ['SendMsg', 'singleMsg'],
     'group_msg' => ['SendMsg', 'channelMsg'],
     '/api/getMsgToken'   => ['MsgApi', 'createMsgToken'],
     '/api/regularOffline'=> ['MsgApi', 'regularOffline'],
     '/api/sendSingleMsg' => ['MsgApi', 'sendSingleMsg'],
     '/api/sendChannelMsg'=> ['MsgApi', 'sendChannelMsg']
   ];

   /**
    * 路由
    * @return [type] [description]
    */
   public static function index($message){
     $path = isset(Routes::$path_array[$message['opt']])?Routes::$path_array[$message['opt']]:'';
     if(isset($message['opt']) && $path){
       return call_user_func_array($path,[$message]);
     }
     //返回错误提示
     Gateway::sendToCurrentClient('{"ERRORCODE":"ME01001","RESULT":"opt is error"}');
     //关闭连接
     Gateway::closeClient($message['client_id']);
     return FALSE;
   }

   /**
    * http 接口路由
    * @param path method body
    * @return [type] [description]
    */
   public static function requestApi($data){
    // 判断路径是否正确
    $path = isset(Routes::$path_array[$data['server']['REQUEST_URI']])?Routes::$path_array[$data['server']['REQUEST_URI']]:'';
    if(!$path){
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01002","RESULT":"REQUEST_URI is error"}');
      return FALSE;
    }
    if(!isset($data['server']['sign'], $data['server']['appKey'], $data['server']['accountID'], $data['server']['timestamp'])){
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01003","RESULT":"head param is error"}');
      return FALSE;
    }
    $data['post']['appKey']     = $data['server']['appKey'];
    $data['post']['accountID']  = $data['server']['accountID'];
    $data['post']['timestamp']  = $data['server']['timestamp'];
    $data['post']['sign']       = $data['server']['sign'];
    //check param
    $rules_array = [
      'appKey'    => ['type'=> 'appKey', 'required'=>true],
      'accountID' => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'sign'      => ['type'=> 'string', 'min'=>5, 'max'=>40, 'required'=>true],
      'timestamp' => ['type'=> 'timestamp', 'min'=>10, 'max'=>10,'required'=>true]
    ];
    $param_status = Common::checkParam($data['post'], $rules_array);
    if($param_status){
      return '{"ERRORCODE":"ME01001","RESULT":'.$param_status.'}';
    }
    $result = call_user_func_array($path,[$data['post']]);
    Gateway::sendToCurrentClient($result);
   }

 }
