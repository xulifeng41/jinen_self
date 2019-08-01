<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 10:03
 */

function fanhui($status=200,$message='',$data=''){
    return ['status' => $status, 'msg' => $message, 'data' => $data];
}

function data_table($uid){
    switch ($uid%8){
        case 1:
            $postfix = 'one';
            break;
        case 2:
            $postfix = 'two';
            break;
        case 3:
            $postfix = 'three';
            break;
        case 4:
            $postfix = 'four';
            break;
        case 5:
            $postfix = 'five';
            break;
        case 6:
            $postfix = 'six';
            break;
        case 7:
            $postfix = 'seven';
            break;
        case 0:
            $postfix = 'eight';
            break;
        default: $postfix = 'eight';
            break;
    }
    return $postfix;
}
