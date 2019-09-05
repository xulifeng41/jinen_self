<?php
namespace app\wechatmall\controller;


use think\Controller;
use think\Db;
use app\wechatmall\model\MallWechatUser;
use app\wechatmall\model\MallRanking;
use app\wechatmall\model\MallHavebuy;
use app\wechatmall\model\MallCashOut;
use app\wechatmall\model\MallBalanceLog;

class Calculate extends Controller
{
    /*
     * 排名，暂按一礼拜
     * */
    public function insert_ranking(MallWechatUser $wuser,MallRanking $ranking)
    {
        $result=$wuser->where(['is_buy'=>1,'user_type'=>2])->field('id,nickname')->order('sales_all desc')->limit(10)->select();
        foreach ($result as $k=>$v)
        {
            $datas[$k]['wuser_id']=$v['id'];
            $datas[$k]['nickname']=$v['nickname'];
        }
        Db::startTrans();
        try {
            //储存
            foreach($datas as $data){
                $ranking->data($data,true)->isUpdate(false)->save();
            }

            // 提交事务
            Db::commit();
            echo 123;
//                return ['code' => 200, 'msg' => lang('审核成功')];
        } catch (\Exception $e) {
//                // 回滚事务
            Db::rollback();
//                return ['code' => 201, 'msg' => lang('审核失败')];
            echo 456;
        }
    }

    /*
     * 已购买，暂按一礼拜加一次
     * */
    public function havebuy(MallWechatUser $wuser,MallHavebuy $havebuy)
    {
        $result=$wuser->where(['is_buy'=>1,'user_type'=>2])->whereTime('create_time', 'week')->field('id,nickname')->select();
        foreach ($result as $k=>$v)
        {
            $datas[$k]['wuser_id']=$v['id'];
            $datas[$k]['nickname']=$v['nickname'];
        }
        Db::startTrans();
        try {
            //储存
            foreach($datas as $data){
                $havebuy->data($data,true)->isUpdate(false)->save();
            }

            // 提交事务
            Db::commit();
            echo 123;
//                return ['code' => 200, 'msg' => lang('审核成功')];
        } catch (\Exception $e) {
//                // 回滚事务
            Db::rollback();
//                return ['code' => 201, 'msg' => lang('审核失败')];
            echo 456;
        }
    }
    /*
     * 各项数据计算
     * */
    public function data_cal(MallWechatUser $wuser,MallBalanceLog $balanceLog,MallCashOut $cashOut)
    {
        //本月15号结束时间戳
        $end=date("Y-m-d H:i:s",mktime(23,59,59,date('m'),15,date('Y')));
        //上个月16号结束时间戳
        $begin =date("Y-m-d H:i:s",mktime(0,0,0,date('m')-1,16,date('Y')));
        $where=array('create_time',$begin,$end);

        $wuser_ids=$wuser->where('is_buy',1)->column('id,user_id');
        foreach ($wuser_ids as $k=>$v)
        {
            $wuser_info=$wuser->get($k);
            $sum1=$balanceLog->type_one($k);
            $sum2=$balanceLog->type_two($k);
            $sum3=$balanceLog->type_three($k);
            $sum1_month=$balanceLog->type_one($k,$where);
            $sum2_month=$balanceLog->type_two($k,$where);
            $sum3_month=$balanceLog->type_three($k,$where);
            if($v)
            {
                //可提佣
                $cashs=$cashOut->where('user_id',$v)->where('status','<>','2')->sum('cash');
                $balance=$sum1+$sum2+$sum3-$cashs;

            }else
            {
                $balance=$sum1+$sum2+$sum3;
            }
            $balance_all=$sum1+$sum2+$sum3;
            $balance_month=$sum1_month+$sum2_month+$sum3_month;
            Db::startTrans();
            try {
                $data['wuser_id']=$k;
                $data['balance']=$balance;
                $data['balance_all']=$balance_all;
                $data['balance_month']=$balance_month;
                $balanceLog->data($data,true)->isUpdate(false)->save();
                $w_data['balance']=$balance;
                $w_data['balance_all']=$balance_all;
                $w_data['balance_month']=$balance_month;
                $w_data['update_time']=date("Y-m-d H:i:s");
                $wuser_info->balance=$balance;
                $wuser_info->balance_all=$balance_all;
                $wuser_info->balance_month=$balance_month;
                $wuser_info->update_time=date("Y-m-d H:i:s");
                $wuser_info->save();
                // 提交事务
                Db::commit();
                echo 789;
//                return ['code' => 200, 'msg' => lang('审核成功')];
            } catch (\Exception $e) {
//                // 回滚事务
                Db::rollback();
//                return ['code' => 201, 'msg' => lang('审核失败')];
                echo 1011;
            }
        }
    }
}