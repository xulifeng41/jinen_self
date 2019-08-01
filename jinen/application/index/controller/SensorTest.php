<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 13:29
 */

namespace app\index\controller;
use think\Controller;
use \GatewayWorker\Lib\Gateway;


class SensorTest extends Controller
{
    public function test(){
        Gateway::$registerAddress = '127.0.0.1:1238';

        //Gateway::sendToAll(hex2bin(preg_replace('# #', '', "01 04 00 00 00 01 31 CA")));
     	 Gateway::sendToGroup('no_scm',hex2bin(preg_replace('# #', '', "01 04 00 00 00 01 31 CA")));
//        Gateway::sendToClient('7f0000010b5400000001', '01 04 00 00 00 10 F1 C6');
        return 'ok';
    }

}