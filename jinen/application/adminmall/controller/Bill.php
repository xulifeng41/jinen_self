<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/9
 * Time: 17:13
 */

namespace app\adminmall\controller;
use think\Request;
use think\facade\Session;
use app\common\model\MallCashOutLogs;
use app\common\model\MallSupBalanceLogs;
use app\common\model\MallSuppliers;
class Bill extends Adminm
{
    public function index(MallCashOutLogs $cashlog,Request $request)
    {
        if($request->isAjax()){
            $page = empty($request->param('page')) ? 1 : $request->param('page');
            $limit = empty($request->param('limit')) ? 10 : $request->param('limit');
            $request->param('progress')!==0 ? $progress = $request->param('progress') : $progress = '' ;
            if($progress)
            {
                $where[] = ['supplier_id','=',Session::get('mall_sup')];
                $where[] = ['progress','=',$progress];
            }else
            {
                $where[] = ['supplier_id','=',Session::get('mall_sup')];
            }
            $list = $cashlog->where([
                $where
            ])->page($page,$limit)->order('create_time','desc')->all();
            $count = $cashlog->where([
                $where
            ])->count();
            return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list->toArray());
        }else{
            $this->assign('title','流水管理');
            return $this->fetch();
        }
    }

    public function cash_out(MallCashOutLogs $cashlog,Request $request)
    {
        if($request->isAjax())
        {
            $params = $request->param();
            $params['supplier_id']=Session::get('mall_sup');
            $params['commission']=0.01;//未知
            $params['actual_cash']=$params['cash']-$params['cash']*0.01;
            $params['progress']=1;
            $params['proof']=rand(1,10000);//未知
            $params['create_time']=date('Y-m-d H:i:s');
            if($cashlog->save($params))
            {
                return ['code' => 200, 'msg' => '申请成功'];
            }else
            {
                return ['code' => 201, 'msg' => '申请失败'];
            }
        } else
        {
            $this->assign('title','申请提现');
            return $this->fetch();
        }
    }

    public function cash_check(MallSuppliers $sup,Request $request,MallSupBalanceLogs $supbalance)
    {
        if($request->isAjax())
        {
            //提现金额
            $cash=floatval($request->param('cash'));
            //账户余额
            $balance=floatval($sup::get(Session::get('mall_sup'))->balance);
            //金额变动
            $balanceup=$supbalance->where('supplier_id',Session::get('mall_sup'))->where('type',1)->sum('balance');
            $balancedown=$supbalance->where('supplier_id',Session::get('mall_sup'))->where('type',2)->sum('balance');
            if($cash>$balance || $cash>$balanceup-$balancedown)
            {
                return ['code' => 201, 'msg' => '提现金额与账户余额不符'];
            }else
            {
                return ['code' => 200, 'msg' => '符合金额要求'];
            }
        }
    }
}