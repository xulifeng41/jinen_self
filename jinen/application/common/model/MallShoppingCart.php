<?php


namespace app\common\model;


use think\Model;

class MallShoppingCart extends Model
{
    public function owner(){
        return $this->belongsTo('Customers','customer_id');
    }


}