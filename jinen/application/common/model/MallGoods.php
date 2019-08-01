<?php


namespace app\common\model;


use think\Model;

class MallGoods extends Model
{
    protected $insert = ['create_time'];
    protected $update = ['update_time'];

    protected function setCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    public function cata(){
        return $this->belongsTo('MallCatagories','cata_id');
    }

    public function supp(){
        return $this->belongsTo('MallSuppliers','supplier_id');
    }

    public function getGoodImgAttr($value){
//        return 'http://192.168.101.13/tp511/public/'.$value;
        return 'http://tp5.xulifeng.site/'.$value;
    }

}