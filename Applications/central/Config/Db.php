<?php namespace Config;
/**
 * mysql配置
 * @author tshanchuan
 */
class Db {
    /**
     * 数据库的一个实例配置，则使用时像下面这样使用
     * $user_array = Db::instance('db1')->select('name,age')->from('users')->where('age>12')->query();
     * 等价于
     * $user_array = Db::instance('db1')->query('SELECT `name`,`age` FROM `users` WHERE `age`>12');
     * @var array
     */
    public static $db1 = [
      'host'    => '127.0.0.1',
      'port'    => 3306,
      'user'    => 'your_user_name',
      'password' => 'your_password',
      'dbname'  => 'your_db_name',
      'charset'    => 'utf8',
    ];
    public static $private_redis = [
     'host'     => '192.168.1.11',
     'port'     => 6349
    ];
    public static $message_redis = [
        'host'     => '192.168.1.11',
        'port'     => 6349
    ];
}
