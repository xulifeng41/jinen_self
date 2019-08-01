<?php
/**
 * User: Tegic
 * Date: 2018/6/13
 * Time: 09:47
 */
namespace app\workerman;
use GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    public static $db;
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
//        Gateway::sendToClient($client_id, json_encode(array('client_id' => $client_id, 'type' => 'login')));
        // 向所有人发送
//        Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
//       收到消息一是注册消息，一个是检测数据
        $message1 = json_decode($message,true);
        if(empty($message1)){
//           //检测数据
            $moniter_data = bin2hex($message);
            $data = substr($moniter_data,0,10);
            $crc16 = self::crc16($data);
            $crc = substr($moniter_data,-4,4);
            //数据校验
            if (strtolower($crc)==$crc16){
                $soot = substr($moniter_data,6,4);
                $soot_shi = hexdec($soot);
                $uid = Gateway::getUidByClientId($client_id);
                $device = self::$db->select('*')
                    ->from('devices')
                    ->where('id = :id')
                    ->bindValues(array('id' => $uid))
                    ->row();
                if (!empty($device)){
                    //防止没有注册的机器写入数据
                    $postfix = $uid%100;
                    $table = 'device_datas_'.$postfix;
                    self::$db->insert($table)
                        ->cols(array('device_id' => $uid,
                            'soot' => $soot_shi/100,
                            'create_time' => date('Y-m-d H:i:s')))
                        ->query();
                }
            }else{
                //修改心跳包
                if (strpos($message,'OK')){
                    if (strpos($message,'{"type":"ping"}')){
//                        Gateway::sendToClient($client_id,"@DTU:0000:POWEROFF");
                    }else{
                        $ping = json_encode(['type' => 'ping']);
                        Gateway::sendToClient($client_id,"@DTU:0000:KEEPALIVE:50,0,0,0,0,$ping");
//                        Gateway::sendToClient($client_id,"@DTU:0000:POWEROFF");
                    }
                }
                self::$db->insert('dtu_link')
                    ->cols(array('code' => $message,
                        'client_id' => $client_id,
                        'create_time' => date('Y-m-d H:i:s')))
                    ->query();
            }
        }else{
            if(isset($message1['type'])){
                switch ($message1['type']){
                    case 'ping':
                        return 'ok';
                        break;
                    case 'login':
                        //注册消息
                        $code = $message1['code'];
                        $device = self::$db->select('*')
                            ->from('devices')
                            ->where('device_code = :device_code')
                            ->bindValues(array('device_code' => $code))
                            ->row();
//           如果没有没有这台设备,就绑定他的code
                        if (empty($device)){
                            Gateway::bindUid($client_id,$code);
                        }else{
                            if($device['is_mine']!=1){
                                Gateway::joinGroup($client_id, 'no_scm');
                            }
                            self::$db->update('devices')
                                ->cols(array('status' => 1,'prev_time' => date('Y-m-d H:i:s')))
                                ->where('id='.$device['id'])
                                ->query();
                            Gateway::bindUid($client_id,$device['id']);
                            $_SESSION['uid'] = $device['id'];
                        }
                        break;
                    default: return 'ok';
                }
            }else{

                $code = $message1['code'];
//                echo $code.' ';
                $device = self::$db->select('*')
                    ->from('devices')
                    ->where('device_code = :device_code')
                    ->bindValues(array('device_code' => $code))
                    ->row();
//           如果没有没有这台设备,就绑定他的code
                if (empty($device)){
                    Gateway::bindUid($client_id,$code);
                }else{
                    if($device['is_mine']!=1){
                        Gateway::joinGroup($client_id, 'no_scm');
                    }
                    self::$db->update('devices')
                        ->cols(array('status' => 1,'prev_time' => date('Y-m-d H:i:s')))
                        ->where('id='.$device['id'])
                        ->query();
                    //修改注册包
                    Gateway::bindUid($client_id,$device['id']);
                    $register_package = json_encode(['type' => 'login', 'code' =>$message1['code']]);
                    Gateway::sendToClient($client_id,"@DTU:0000:DTUID:1,0,$register_package");
                    $_SESSION['uid'] = $device['id'];
                }
            }
            //记录注册包
            $link = self::$db->select('*')
                    ->from('dtu_link')
                    ->where('code = :code')
                    ->bindValues(array('code' => $message1['code']))
                    ->row();
            if (empty($link)){
                self::$db->insert('dtu_link')
                    ->cols(array('code' => $message1['code'],
                        'client_id' => $client_id,
                        'create_time' => date('Y-m-d H:i:s')))
                    ->query();
            }


        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
        $uid = $_SESSION['uid'];
        if (!empty($uid)){
            $now = date('Y-m-d H:i:s');
            self::$db->query("UPDATE `devices` SET `status` = 2, total_time= (total_time+UNIX_TIMESTAMP('$now')-UNIX_TIMESTAMP(prev_time)) WHERE id = $uid");
            $_SESSION['uid'] = '';
        }
    }

    public static function onWorkerStart($gateway)
    {
        // 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
        self::$db = new \Workerman\MySQL\Connection('127.0.0.1', '3306', 'jinen', 'JinEn@2019', 'jinen');
    }

    public static function crc16($string){
        $string = pack('H*', $string);
        $crc = 0xFFFF;
        for ($x = 0; $x < strlen ($string); $x++) {
            $crc = $crc ^ ord($string[$x]);
            for ($y = 0; $y < 8; $y++) {
                if (($crc & 0x0001) == 0x0001) {
                    $crc = (($crc >> 1) ^ 0xA001);
                } else {
                    $crc = $crc >> 1;
                }
            }
        }
        return sprintf('%02x%02x', $crc%256, floor($crc/256));
    }

}
