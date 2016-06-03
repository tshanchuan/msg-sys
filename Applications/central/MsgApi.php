<?php

require_once('workerman-statistics/Applications/Statistics/Clients/StatisticClient.php');
require_once('Common.php');
require_once('SendMsg.php');

use \GatewayWorker\Lib\Gateway;
use Msg\Central\Common;
use Config\Db as DbConfig;
/**
 * 消息系统接口
 */
class MsgApi {

  /**
   * 创建msgToken 并保存在redis中
   * @param  appKey accountID timestamp
   * @return msgToken
   */
  public static function createMsgToken($data){
    //建立监控
    StatisticClient::tick("MsgApi", 'getMsgToken');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $check_sign = Common::checkSign($data);
    if(!$check_sign){
      return '{"ERRORCODE":"ME01001","RESULT":"sign is error"}';
    }
    StatisticClient::report('MsgApi', 'getMsgToken', $success, $code, $msg);
    //存取msgToken
    $msgToken = Common::saddMsgToken();
    if($msgToken){
      return '{"ERRORCODE":0,"RESULT":"{"accountID":"'.$data['accountID'].'","msgToken":"'.$msgToken.'"}';
    }
    return '{"ERRORCODE":"ME01003","RESULT":"create msgToken error"}';
  }

  /**
   * 定时清除超时连接
   * @return [type] [description]
   */
  public static function regularOffline($data){
    //获取当前所有在线client_id信息
    $all_client = Gateway::getALLClientInfo();
    foreach ($all_client as $key => $value) {
      if(count($value) == 0 && $key != $_SERVER['GATEWAY_CLIENT_ID']){
        //关闭连接
        Gateway::closeClient($key);
      }
    }
    return '{"ERRORCODE":0,"RESULT":"ok"}';
  }

  /**
   * 推送单点消息
   * @param  [type] $data [description]
   * @return [type]       [description]
   */
  public static function sendSingleMsg($data){
    $check_sign = Common::checkSign($data);
    if(!$check_sign){
      return '{"ERRORCODE":"ME01001","RESULT":"sign is error"}';
    }
    //建立监控
    StatisticClient::tick("MsgApi", 'sendSingleMsg');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $data['opt'] = 'single_msg';
    $data['msgObj'] = json_decode($data['msgObj'], TRUE);
    SendMsg::singleMsg($data);
    // 上报结果
    StatisticClient::report('MsgApi', 'getMsgToken', $success, $code, $msg);
  }

  /**
   * 推送频道消息
   * @param  [type] $data [description]
   * @return [type]       [description]
   */
  public static function sendChannelMsg($data){
    $check_sign = Common::checkSign($data);
    if(!$check_sign){
      return '{"ERRORCODE":"ME01001","RESULT":"sign is error"}';
    }
    //建立监控
    StatisticClient::tick("MsgApi", 'sendChannelMsg');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    //验证基本参数
    $data['opt'] = 'group_msg';
    $data['msgObj'] = json_decode($data['msgObj'], TRUE);
    SendMsg::channelMsg($data);
    // 上报结果
    StatisticClient::report('MsgApi', 'getMsgToken', $success, $code, $msg);
  }
}
