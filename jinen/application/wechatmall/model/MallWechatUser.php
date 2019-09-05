<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/1
 * Time: 9:45
 */

namespace app\wechatmall\model;


use think\Model;
use app\wechatmall\model\MallWechatQrcode;
class MallWechatUser extends Model
{
    public function user(){
        return $this->belongsTo('MallUser','user_id');
    }

    public function user_address(){
        return $this->hasMany('MallUserAddress','wuser_id');
    }

    public function orders(){
        return $this->hasMany('MallOrder','wuser_id');
    }

    public function bills(){
        return $this->hasMany('MallBill','wuser_id');
    }

    public function codes(){
        return $this->hasOne('MallWechatQrcode','wuser_id');
    }

    //判断用户是否能生成自己的二维码
    public function is_buy($value)
    {
        $info = self::where('openid', $value)->find();
            return $info->is_buy;
    }



    public function get_num($id)
    {
        $result['all']=self::where('second',$id)->count();
        $result['buy_num']=self::where('second',$id)->where('is_buy',1)->count();
        $result['nobuy_num']=self::where('second',$id)->where('is_buy',2)->count();
        return $result;
    }
}