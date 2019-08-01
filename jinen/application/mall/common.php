<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 10:03
 */

defined('HOST_NAME') ? '' : define("HOST_NAME",'http://test.jinenhb.com/');

function err_return($msg){
    $data = array();
    $data['code'] = 201;
    $data['msg'] = $msg;
    return $data;
}

function right_return($data=array(),$msg=''){
    $data1 = array();
    $data1['code'] = 200;
    $data1['msg'] = $msg;
    $data1['data'] = $data;
    return $data1;
}

