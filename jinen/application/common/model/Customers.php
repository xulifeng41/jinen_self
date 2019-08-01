<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:41
 */

namespace app\common\model;


use think\Model;

class Customers extends Model
{
    public function devices(){
        return $this->hasMany('Devices','customer_id');
    }

    public function mOrders(){
        return $this->hasMany('MallOrders','customer_id');
    }

    public function recAddrs(){
        return $this->hasMany('MallReceivingAddress','customer_id');
    }

    public function cartGoods(){
        return $this->hasMany('MallShoppingCart','customer_id');
    }

    public function sup(){
        return $this->hasOne('MallSuppliers','customer_id');
    }

    public function getDizhiAttr($value,$data){
        $dizhi = Region::where('id','in',[$data['province'],$data['city'],$data['area']])->field('id,name')->all();
        $dizhi1 = array_column($dizhi->toArray(),'name');
        $xian = isset($dizhi1[2]) ? $dizhi1[2] : '';
        return $dizhi1[0].$dizhi1[1].$xian.$data['detail_address'];
    }

    public function getSalemanAttr($value){
        return self::getFieldById($value,'cus_name');
    }

    public function getHeadimgurlAttr($value){
        return 'http://192.168.101.13/tp511/public/'.$value;
    }

}