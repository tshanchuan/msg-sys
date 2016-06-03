<?php namespace Msg\Central;

require_once('validate.class.php');

use \GatewayWorker\Lib\Gateway;
use Config\Db as DbConfig;
use Msg\Central\Validate as Validate;
use Redis as Redis;
/**
 *
 * 公共方法类库
 *
 */
class Common {

  /**
   *
   * 根据client_id 绑定 accountID
   * @param client_id accountID
   * @return bool
   */
  public static function bindAccount($client_id, $accountID){
    return Gateway::bindUid($client_id, $accountID);
  }

  /**
   *
   * 根据accountID 获取client_id
   * @param accountID
   * @return array
   */
  public static function isUidOnline($accountID){
    return Gateway::isUidOnline($accountID);
  }

  /**
   *
   * 将client_id 根据频道编号加入频道
   */

  public static function joinChannel($client_id, $groupID){
    return Gateway::joinGroup($client_id, $groupID);
  }

  /**
   *
   * 将client_id 根据频道编号移除
   */

  public static function leaveChannel($client_id, $groupID){
    return Gateway::leaveGroup($client_id, $groupID);
  }

  /**
   *
   * 根据经纬度创建附近频道模式ID
   * @param longitude latitude
   * @return channelID
   */
  public static function getNearByChannelID($latitude, $longitude){
    $reg_lon = "/^[-\+]?((1[0-7]\d{1}|0?\d{1,2})\.\d{1,5}|180\.0{1,5})$/";
    $reg_lat = "/^[-\+]?([0-8]?\d{1}\.\d{1,5}|90\.0{1,5})$/";
    if(!preg_match($reg_lon, $longitude) && !preg_match($reg_lat, $latitude)){
      return "nearby_0_0";
    }

		$lon = floor($longitude / 4);
		$lat = floor($latitude / 4);
		return "nearby_".$lon."_".$lat;
  }

  /**
   * 验证参数是否合法
   * @param  [type] $param [description]
   * @return [type]        [description]
   */
  public static function checkParam($param, $rules){
    $val = new Validate;
    $val->addSource($param);
    $val->addRules($rules);
    $val->run();
    if(count($val->errors) > 0){
      return json_encode($val->errors);
    }
    return '';
  }

  /**
   * 保存数据至session
   * @param [type] $client_id [description]
   * @param [type] $data      [description]
   * @return status
   */
  public static function setSession($client_id, $data){
    return Gateway::setSession($client_id, $data);
  }

  /**
   * 根据 client_id 获取session
   * @param  [type] $client_id [description]
   * @return [type]            [description]
   */
  public static function getSession($client_id){
    return Gateway::getSession($client_id);
  }

  /**
   * 根据accountID 发送消息
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public static function sendToUid($message){
    $message['opt'] = 'msg';
    $message['msgTime'] = time();
    $message['msgID'] = Common::getMsgTokenID('msgID');
    unset($message['client_id']);
    Gateway::sendToUid($message['toUser'], json_encode($message));
    return $message['msgID'];
  }

  /**
   * 根据groupID 发送消息
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public static function sendToGroup($message){
    $message['opt'] = 'msg';
    $message['msgTime'] = time();
    $message['groupID'] = $message['toGroup'];
    $message['msgID'] = Common::getMsgTokenID('msgID');
    unset($message['client_id']);
    unset($message['toGroup']);
    Gateway::sendToGroup($message['groupID'], json_encode($message));
    return $message['msgID'];
  }

  /**
   * 获取msgToken msgID
   * @return [type] [description]
   */
  public static function getMsgTokenID($type="msgToken"){
    $sec_time  = gettimeofday();
    if($type == "msgID"){
      return date('Ymdhis').$sec_time['usec'];
    }
    return md5(date('Ymdhis').$sec_time['usec']);
  }

  /**
   * msgToken 存redis
   * @return [type] [description]
   */
  public static function saddMsgToken(){
    $redis = new Redis();
    $redis_config = DbConfig::$message_redis;
    $redis->connect($redis_config['host'], $redis_config['port']);
    $msgToken  = Common::getMsgTokenID('msgToken');
    if($redis->sadd('msgToken',$msgToken)){
      return $msgToken;
    }
    return 0;
  }
  /**
  * check sign
  * @param array
  * @return  true false
  */
  public static function checkSign($array){
    $p_sign = $array['sign'];
    unset($array['sign']);
    $array['secret'] = Common::getSecret($array['appKey']);
    foreach ($array as $key=>$value){
        $arr[$key] = $key;
    }
    sort($arr);
    $str = "";
    foreach ($arr as $k => $v){
        $str = $str.$arr[$k].$array[$v];
    }
    $sign = strtoupper(sha1($str));
    // print_r($sign);
    if($p_sign == $sign){
      return TRUE;
    }
    return FALSE;
  }

  /**
  * 通过缓存获取 secret
  * @param appKey
  * @return secret
  */
  public static function getSecret($appKey){
    $redis = new Redis();
    $redis_config = DbConfig::$message_redis;
    $redis->connect($redis_config['host'], $redis_config['port']);
    return $redis->hget($appKey.':appKeyInfo','secret');
  }
}
