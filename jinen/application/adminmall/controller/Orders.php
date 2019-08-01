<?php


namespace app\adminmall\controller;


use think\Request;
use think\facade\Session;
use app\common\model\MallOrders;
use app\common\model\Customers;
use think\Db;
use app\common\model\MallReturnGood;
use app\common\model\MallDeliveryLogs;
use app\common\model\MallOrderEvaluates;
class Orders extends Adminm
{
    public function index(MallOrders $orders,Request $request)
    {
        if($request->isAjax()){
            $page = empty($request->param('page')) ? 1 : $request->param('page');
            $limit = empty($request->param('limit')) ? 10 : $request->param('limit');
            $request->param('status')!==0 ? $status = $request->param('status') : $status = '' ;
            if($status)
            {
                if($status==7)
                {
                    $where[] = ['supplier_id','=',Session::get('mall_sup')];
                }else
                {
                    $where[] = ['supplier_id','=',Session::get('mall_sup')];
                    $where[] = ['order_status','=',$status];
                }
            }else
            {
                $where[] = ['supplier_id','=',Session::get('mall_sup')];
                $where[] = ['order_status','=',2];
            }
            $list = $orders->where([
                $where
            ])->page($page,$limit)->order('create_time','desc')->all();
            $count = $orders->where([
                $where
            ])->count();
            return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list->toArray());
        }else{
            $add=Db::view(['mall_receiving_address' => 'd'], 'id,detail_address')
                ->view(['region' => 'a'], ['name' => 'aname'], 'd.province=a.id','LEFT')
                ->view(['region' => 'b'], ['name' => 'bname'], 'd.city=b.id','LEFT')
                ->view(['region' => 'c'], ['name' => 'cname'], 'd.area=c.id','LEFT')
                ->select();
            $this->assign('add',$add);
            $this->assign('title','订单管理');
            return $this->fetch();
        }
    }

    public function edit_orders(MallOrders $orders, Request $request, MallReturnGood $return,MallDeliveryLogs $delivery,MallOrderEvaluates $comment)
    {
        $oid = $request->param('id');
        if ($request->isAjax())
        {
            switch ($request->param('type'))
            {
                case 'send':
                    $order = $orders::get($oid);
                    $order->order_status = 4;
                    $order->update_time = date('Y-m-d H:i:s');
                    $result = $order->save();
                    if($result){
                        return ['code'=>200,'msg'=>'成功发货'];
                    }else{
                        return ['code'=>201,'msg'=>'取消发货'];
                    }
                    break;
                case 'info':
                    $order = $orders::info($oid);
                    if($order)
                    {
                        return ['code'=>200,'msg'=>'正在查询'];
                    }else
                    {
                        return ['code'=>201,'msg'=>'订单错误'];
                    }
                    break;
            }
        }else
        {
            switch ($request->param('type'))
            {
                case 'return':
                    $info = $return->where('order_id',$oid)->find();
                    $this->assign('info',$info);
                    $this->assign('type','return');
                    return $this->fetch();
                    break;
                case 'delivery':
                    $info = $delivery->where('order_id',$oid)->where('supplier_id',Session::get('mall_sup'))->find();
                    $this->assign('info',$info);
                    $this->assign('type','delivery');
                    return $this->fetch();
                    break;
                case 'comment':
                    $info = $comment->where('order_id',$oid)->find();
                    $this->assign('info',$info);
                    $this->assign('type','comment');
                    return $this->fetch();
                    break;
            }
        }
    }

    public function info_orders(Request $request,MallOrders $orders,Customers $cus)
    {
        if($request->isAjax()){
            $oid = $request->param('oid');
            $num=intval($oid)%10==0?10:intval($oid)%10;
            $view="mall_order_goods_".$num;
            $limit = $request->param('limit');
            $page = $request->param('page');
            $list = Db::table("$view")->where('order_id',$oid)->where('supplier_id',Session::get('mall_sup'))->page($page,$limit)->order('id','asc')->all();
            $count = Db::table("$view")->where('order_id',$oid)->where('supplier_id',Session::get('mall_sup'))->count();
            return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list);
        }else{
            $oid = empty($request->param('id')) ? '' : $request->param('id');
            $result=$orders::get($oid);
            $result['supplier_id']==Session::get('mall_sup')?$this->assign('oid',$oid):$this->assign('oid','');
            $this->assign('title','订单详情');
            return $this->fetch();
        }
    }
}