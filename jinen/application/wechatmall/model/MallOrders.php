<?php
namespace app\wechatmall\model;


use think\Model;

class MallOrders extends Model
{
    public function good()
    {
        return $this->hasOne('MallOrderGoods','order_id');
    }

    public function pay()
    {
        return $this->hasOne('MallPayLogs','order_id');
    }

    public function address()
    {
        return $this->belongsTo('MallUserAddress','user_address');
    }

    public function wuser()
    {
        return $this->belongsTo('MallWechatUser','wuser_id');
    }
}