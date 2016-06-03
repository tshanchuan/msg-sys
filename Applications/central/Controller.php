<?php

require_once('workerman-statistics/Applications/Statistics/Clients/StatisticClient.php');
require_once('Common.php');
use Redis as Redis;
use Msg\Central\Common;
use \GatewayWorker\Lib\Gateway;
class Controller {

  /**
   *
   * 客户端建立连接
   * @param client_id opt accountID lat lon ...
   * @return bool
   */
  public static function onConnect($message){
    //建立监控
    StatisticClient::tick("Msg", 'connect');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $rules_array = [
      'opt'       => ['type'=> 'string', 'min'=>5, 'max'=>10, 'required'=>true],
      'accountID' => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'msgToken'  => ['type'=> 'tokenCode', 'required'=>true],
      'timestamp' => ['type'=> 'timestamp', 'required'=>true],
      'lon'       => ['type'=> 'latlon', 'required'=>true],
      'lat'       => ['type'=> 'latlon', 'required'=>true]
    ];
    $param_status = Common::checkParam($message, $rules_array);
    if($param_status){
      //返回错误提示
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01000","RESULT":"'.$param_status.'"}');
      //关闭连接
      Gateway::closeClient($message['client_id']);
      return FALSE;
    }
    //关联client_id accountID
    Common::bindAccount($message['client_id'], $message['accountID']);
    //生成附近群组编号
    $nearbyChannelID = Common::getNearByChannelID($message['lat'], $message['lon']);
    Common::joinChannel($message['client_id'], $nearbyChannelID);

    $result = [
      "ERRORCODE" => 0,
      "RESULT" => [
        "channelID" => $nearbyChannelID,
        "accountID" => $message['accountID'],
      ]
    ];
    // 上报结果
    $msg = $message['client_id'];
    StatisticClient::report('Msg', 'connect', $success, $code, $msg);
    // session save
    Common::setSession($message['client_id'], $result['RESULT']);
    $result_json = json_encode($result);
    Gateway::sendToGroup($nearbyChannelID, $result_json);
    Gateway::sendToCurrentClient($result_json);
  }

  /**
   *
   * 客户端连接后,心跳消息机制
   * @param client_id opt accountID lat lon ...
   * @return bool
   */
   public static function onHeart($message){
     //验证基本参数
     $rules_array = [
       'opt'        => ['type'=> 'string', 'min'=>5, 'max'=>10, 'required'=>true],
       'accountID'  => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
       'msgToken'   => ['type'=> 'string', 'min'=>5, 'max'=>30, 'required'=>true],
       'timestamp'  => ['type'=> 'timestamp', 'required'=>true],
       'latlon'     => ['type'=> 'array', 'required'=>true]
     ];
     $param_status = Common::checkParam($message, $rules_array);
     if($param_status){
       //返回错误提示
       Gateway::sendToCurrentClient('{"ERRORCODE":"ME01001","RESULT":"'.$param_status.'"}');
       return FALSE;
     }
     //判断收件方是否在线
     if(Common::isUidOnline($message['accountID']) == 0){
       //返回错误提示
       Gateway::sendToCurrentClient('{"ERRORCODE":"ME01004",RESULT:"toUser is offline"}');
       return FALSE;
     }
     //获取最后一个位置
     $last_latlon = $message['latlon'][count($message['latlon'])-1];
     //生成附近群组编号
     $nearbyChannelID = Common::getNearByChannelID($last_latlon['N'], $last_latlon['E']);
     //离开上一个分组,进入新分组
     $session = Common::getSession($message['client_id']);
     if($nearbyChannelID != $session['channelID']){
       Common::leaveChannel($message['client_id'], $session['channelID']);
       Common::joinChannel($message['client_id'], $nearbyChannelID);
     }
   }

   public static function offline($message){
     // 统计开始
    StatisticClient::tick("Msg", 'offline');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
     //验证基本参数
     $rules_array = [
       'opt'        => ['type'=> 'string', 'min'=>5, 'max'=>10, 'required'=>true],
       'accountID'  => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
       'msgToken'   => ['type'=> 'string', 'min'=>5, 'max'=>30, 'required'=>true],
       'timestamp'  => ['type'=> 'timestamp', 'required'=>true],
       'lon'        => ['type'=> 'latlon', 'required'=>true],
       'lat'        => ['type'=> 'latlon', 'required'=>true]
     ];
     $param_status = Common::checkParam($message, $rules_array);
     if($param_status){
       //返回错误提示
       Gateway::sendToCurrentClient('{"ERRORCODE":"ME01001","RESULT":"'.$param_status.'"}');
       return FALSE;
     }
     //返回 ok
     Gateway::sendToCurrentClient('{"ERRORCODE":"0",RESULT:{"opt":"breakoff","breakoffType":1,"hint":"您的帐号在其他机器登陆，请重新登录。"}}');
     //关闭连接
     Gateway::closeClient($message['client_id']);
     // 上报结果
     StatisticClient::report('Msg', 'offline', $success, $code, $msg);
   }

}
