<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/13
 * Time: 16:51
 */

namespace app\wechatmall\model;


use think\Model;

class MallPayLogs extends Model
{
    public function order()
    {
        return $this->belongsTo('MallOrders','order_id');
    }
}