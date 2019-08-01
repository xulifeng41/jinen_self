<?php


namespace app\common\model;


use think\Model;
use app\common\model\Customers;
use think\facade\Session;
class MallSuppliers extends Model
{
    public function ddans(){
        return $this->hasMany('MallOrders','supplier_id');
    }

    public function shopg(){
        return $this->hasMany('MallGoods','supplier_id');
    }

    public function user(){
        return $this->belongsTo('Customers','customer_id');
    }

    public function cashOuts(){
        return $this->hasMany('MallCashOutLogs','supplier_id');
    }

    public function balanceLogs(){
        return $this->hasMany('MallSupBalanceLogs','supplier_id');
    }

    public function getDizhiAttr($value,$data){
        $dizhi = Region::whereIn('id',[$data['province'],$data['city'],$data['area']])->column('name','id');
        return $dizhi[$data['province']].' '.$dizhi[$data['city']].' '.$dizhi[$data['area']];
    }

    public function getIdCardFrontAttr($value){
        return 'http://192.168.101.13/tp511/public/'.$value;
    }

    public function getIdCardBackAttr($value){
        return 'http://192.168.101.13/tp511/public/'.$value;
    }

    public function getBusinessLicenseAttr($value){
        return 'http://192.168.101.13/tp511/public/'.$value;
    }

    // 供应商后台登录验证
    public static function login_validate($params)
    {
        $user_name = $params['admin_name'];
        $user_password = $params['admin_password'];
        $user = Customers::where(['phone' => $user_name])->where('is_supplier=1 or is_agent=1')->find();
        if (empty($user)) {
            return ['status' => 201, 'msg' =>lang('empty_user')];
        }
        if ($user['password']!=md5(md5($user_password).$user['salt'])) {
            return ['status' => 201, 'msg' =>lang('wrong_pwd')];
        }
        $ids = self::get(['customer_id'=>$user['id']]);
        $user['sid']=$ids['id'];
        return ['status' => 200, 'data' => $user];
    }

    public function balance()
    {
        $sid=Session::get('mall_sup');
        $balance=self::where('id',$sid)->value('balance');
        return $balance;
    }
}