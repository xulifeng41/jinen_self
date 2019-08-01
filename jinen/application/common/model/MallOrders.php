<?php


namespace app\common\model;


use think\Model;
use think\facade\Session;
class MallOrders extends Model
{
    protected $insert = ['create_time'];
    protected $update = ['update_time'];

    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    protected function setUpdateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    public function cust(){
        return $this->belongsTo('Customers','customer_id');
    }

    public function supplier(){
        return $this->belongsTo('MallSuppliers','supplier_id');
    }

    public function delivery(){
        return $this->hasOne('MallDeliveryLogs','order_id');
    }

    public function refundg(){
        return $this->hasOne('MallReturnGood','order_id');
    }

    public function scopeWaitpay($qurey)
    {
        $qurey->where(['order_status' => 1]);
    }

    public function scopeWaitdeliver($qurey)
    {
        $qurey->where(['order_status' => 2]);
    }

    public function scopeDelivering($qurey)
    {
        $qurey->where(['order_status' => 4]);
    }

    public function scopeWaitcomment($qurey)
    {
        $qurey->where(['order_status' => 5]);
    }

    public function scopeCancel($qurey)
    {
        $qurey->where(['order_status' => 3]);
    }

    public function num()
    {
        $sid=Session::get('mall_sup');
        $count=self::where('supplier_id',$sid)->where('order_status',2)->count();
        return $count;
    }

    static function info($oid){
        $info=self::where('id',$oid)->find();
        $sid=Session::get('mall_sup');
        if($info['supplier_id']==$sid)
        {
            return true;
        }else
        {
            return false;
        }
    }
}