<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/1
 * Time: 9:45
 */

namespace app\wechatmall\model;


use think\Model;

class MallWechatQrcode extends Model
{
    public function user(){
        return $this->belongsTo('MallUser','user_id');
    }


}