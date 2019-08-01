<?php


namespace app\common\model;


use think\Model;

class MallDeliveryLogs extends Model
{
    public function order()
    {
        return $this->belongsTo('MallOrders','order_id');
    }

    public function getDizhiAttr($value,$data){
        $dizhi = Region::where('id','in',[$data['province'],$data['city'],$data['area']])->field('id,name')->all();
        $dizhi1 = array_column($dizhi->toArray(),'name');
        $xian = isset($dizhi1[2]) ? $dizhi1[2] : '';
        return $dizhi1[0].$dizhi1[1].$xian.$data['detail_address'];
    }

}