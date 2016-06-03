<?php
/**
 * tshanchuan
 */
require_once('workerman-statistics/Applications/Statistics/Clients/StatisticClient.php');
require_once('Common.php');

use Msg\Central\Common;
use \GatewayWorker\Lib\Gateway;

/**
 * 推送消息
 */
class SendMsg {

  /**
   * 推送单点消息
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public static function singleMsg($message){
    //建立监控
    StatisticClient::tick("SendMsg", 'sendSingleMsg');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $rules_array = [
      'opt'       => ['type'=> 'string', 'min'=>5, 'max'=>10, 'required'=>true],
      'fromUser'  => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'toUser'    => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'holdTime'  => ['type'=> 'numeric', 'min'=>0, 'max'=>5, 'required'=>true],
      'msgType'   => ['type'=> 'string', 'min'=>4, 'max'=>11, 'required'=>true],
      'msgObj'    => ['type'=> 'array', 'required'=>true]
    ];
    $param_status = Common::checkParam($message, $rules_array);
    if($param_status){
      //返回错误提示
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01001","RESULT":"'.$param_status.'"}');
      return FALSE;
    }
    //判断收件方是否在线
    if(Common::isUidOnline($message['toUser']) == 0){
      //返回错误提示
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01004",RESULT:"toUser is offline"}');
      return FALSE;
    }
    //发送消息
    $msgID = Common::sendToUid($message);
    Gateway::sendToCurrentClient('{"ERRORCODE":"0",RESULT:{"msgID":"'.$msgID.'"}');
    // 上报结果
    StatisticClient::report('SendMsg', 'sendSingleMsg', $success, $code, $msg);
  }

  /**
   * 推送群组消息
   * @param [type] $message [description]
   */
  public static function channelMsg($message){
    //建立监控
    StatisticClient::tick("SendMsg", 'sendChannelMsg');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $rules_array = [
      'opt'       => ['type'=> 'string', 'min'=>5, 'max'=>10, 'required'=>true],
      'fromUser'  => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'toGroup'    => ['type'=> 'string', 'min'=>5, 'max'=>11, 'required'=>true],
      'holdTime'  => ['type'=> 'string', 'min'=>0, 'max'=>5, 'required'=>true],
      'msgType'   => ['type'=> 'string', 'min'=>4, 'max'=>11, 'required'=>true],
      'msgObj'    => ['type'=> 'array', 'required'=>true]

    ];
    $param_status = Common::checkParam($message, $rules_array);
    if($param_status){
      //返回错误提示
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01001","RESULT":"'.$param_status.'"}');
      return FALSE;
    }
    $online_count = Gateway::getClientCountByGroup($message['toGroup']);
    if($online_count == 0){
      //返回错误提示
      Gateway::sendToCurrentClient('{"ERRORCODE":"ME01006",RESULT:"group no one"}');
      return FALSE;
    }

    //发送消息
    $msgID = Common::sendToGroup($message);
    Gateway::sendToCurrentClient('{"ERRORCODE":"0",RESULT:{"msgID":"'.$msgID.'","count":'.$online_count.'}}');

    // 上报结果
    StatisticClient::report('SendMsg', 'sendChannelMsg', $success, $code, $msg);
  }
}
