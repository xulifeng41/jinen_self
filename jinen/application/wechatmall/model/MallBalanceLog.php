<?php
namespace app\wechatmall\model;

use app\wechatmall\model\MallBill;

use think\Model;

class MallBalanceLog extends Model
{
    //获取用户返利增加type=1
    public function type_one($wuser_id,$where=[])
    {
        if(count($where))
        {
            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>1])->whereBetweenTime($where[0],$where[1],$where[2])->sum('balance');
        }else
        {
            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>1])->sum('balance');
        }
        return $result;
    }
    //获取下线发展增加type=2
    public function type_two($wuser_id,$where=[])
    {
        if(count($where))
        {
            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>2])->whereBetweenTime($where[0],$where[1],$where[2])->sum('balance');
        }else
        {

            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>2])->sum('balance');
        }
        return $result;
    }
    //获取用户中奖增加type=3
    public function type_three($wuser_id,$where=[])
    {
        if(count($where))
        {
            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>3])->whereBetweenTime($where[0],$where[1],$where[2])->sum('balance');
        }else
        {

            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>3])->sum('balance');
        }
        return $result;
    }
    //获取用户提现减少type=5
    public function type_five($wuser_id,$where=[])
    {
        if(count($where))
        {
            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>5])->whereBetweenTime($where[0],$where[1],$where[2])->sum('balance');
        }else
        {

            $result=MallBill::where(['wuser_id'=>$wuser_id,'type'=>5])->sum('balance');
        }
        return $result;
    }
}