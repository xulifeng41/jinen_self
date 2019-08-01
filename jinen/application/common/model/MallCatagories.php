<?php


namespace app\common\model;


use think\Model;

class MallCatagories extends Model
{
    public function goods(){
        return $this->hasMany('MallGoods','cata_id');
    }
}