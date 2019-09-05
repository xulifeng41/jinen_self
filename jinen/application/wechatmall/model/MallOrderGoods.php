<?php
namespace app\wechatmall\model;


use think\Model;

class MallOrderGoods extends Model
{
    public function order()
    {
        return $this->belongsTo('MallOrder','order_id');
    }
}