<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 16:40
 */

namespace app\index\controller;
use GatewayWorker\Lib\Db;
use think\Controller;
use \GatewayWorker\Lib\Gateway;

class Dtu extends Controller
{
    public function test(){
        Gateway::$registerAddress = '127.0.0.1:1238';
        if (Gateway::isUidOnline(3)){
            echo 2;
            Gateway::sendToUid('3',hex2bin(preg_replace('# #', '', "01 04 00 00 00 01 31 CA")));
            return 'ok';
        }


    }

}