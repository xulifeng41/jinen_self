<?php
namespace app\wechatmall\controller;

use app\wechatmall\model\MallOrders;
use app\wechatmall\model\MallUserAddress;
use app\wechatmall\model\MallCart;
use app\wechatmall\model\MallOrderExpress;
use app\wechatmall\model\MallExpress;
use app\wechatmall\model\MallReturn;
use app\wechatmall\model\MallOrderGoods;
use app\wechatmall\model\MallPayLogs;
use app\wechatmall\model\MallWechatUser;
use app\common\model\Region;
use think\facade\Session;
use think\Request;
use think\Db;
class Order extends Base
{
    public function index()
    {
        $this->assign('title','我的订单');
        return $this->fetch();
    }

    public function order_list(Request $request)
    {
        $datas=$request->param();

        $order_status=(isset($datas['status'])&&$datas['status'])?$request->param('status'):'0';
        if($order_status)
        {
            $where[] = ['mall_orders.wuser_id', '=',Session::get('wuser_id')];
            $where[] = ['order_status','=',$order_status];
        }else
        {
            $where[] = ['mall_orders.wuser_id', '=',Session::get('wuser_id')];
        }
        $result=Db::view('mall_orders', 'id,total_price,total_postage,order_status,order_sn')
            ->view('mall_order_goods', 'good_name,good_img,good_info,good_price,good_nums', 'mall_orders.id=mall_order_goods.order_id')
            ->where($where)
            ->page($datas['page'],'5')
            ->order('mall_orders.create_time','desc')
            ->select();
        $count=Db::view('mall_orders', 'id,total_price,total_postage,order_status')
            ->view('mall_order_goods', 'good_name,good_img,good_info,good_price,good_nums', 'mall_orders.id=mall_order_goods.order_id')
            ->where($where)
            ->count();
        $infos=parent::paging($count,$result,$datas['page']);
        return $infos;
    }

    public function order_details(Request $request,MallOrders $orders,MallUserAddress $address)
    {
        $order_id=$request->param('id');
        $order_info=$orders->get($order_id);
        $add_info=$address->where('id',$order_info->user_address)->find();
        $dizhi = Region::where('id','in',[$add_info['province'],$add_info['city'],$add_info['area']])->field('id,name')->order('id','asc')->select()->toArray();
        $add_info['region']=implode('',array_column($dizhi,'name'));
        $this->assign('add_info',$add_info);
        if($order_info->order_status!=1 && $order_info->order_status!=3)
        {
            $pay_time=$order_info->pay->time_end;
            $this->assign('pay_time',$pay_time);
        }
        $this->assign('order_info',$order_info);
        $this->assign('title','订单详情');
        return $this->fetch();
    }

    public function create_order(Request $request,MallUserAddress $address,MallCart $cart)
    {
        $datas=$request->param();
        if(isset($datas['add_id'])&&$datas['add_id'])
        {
            $add_info=$address->get($datas['add_id']);
        }else
        {
            $add_info=$address->where(['wuser_id'=>Session::get('wuser_id'),'is_default'=>1])->find();
        }
        if($add_info)
        {
            $dizhi = Region::where('id','in',[$add_info['province'],$add_info['city'],$add_info['area']])->field('id,name')->order('id','asc')->select()->toArray();
            $add_info['region']=implode('',array_column($dizhi,'name'));
        }else
            {
                $add_info=false;
            }

        $cart_ids=explode(',',$datas['cart_ids']);
        foreach($cart_ids as $k=>$v)
        {
            $cart_info=$cart->get($v);
            $total_price[]=$cart_info->good_price*$cart_info->good_nums;
            $cart_infos[]=$cart->get($v);
        }
        $info['total_price']=array_sum($total_price);
        $info['total_postage']=0;
        $info['pay_price']=$info['total_price']+$info['total_postage'];
        $this->assign('cart_info',$cart_infos);
        $this->assign('info',$info);
        $this->assign('add_info',$add_info);
        $this->assign('cart_ids',$datas['cart_ids']);
        $this->assign('title','订单生成');
        return $this->fetch();
    }

    public function progress(Request $request,MallOrderExpress $order_e,MallExpress $express,MallOrders $orders,MallReturn $returns)
    {
        if($request->isAjax())
        {
            $data=$request->param();
            $order_id=$data['order_id'];
            $order_status=$data['order_status'];
            $type=$data['type'];
            $order_info=$orders->get($order_id);
            switch ($order_status)
            {
                case 1:
                    if($type==1)
                    {
                        $openid=$order_info->wuser->openid;
                        //生成json
                        include_once '../extend/weixin1/WxPayPubHelper.php';
                        $jsApi = new \JsApi_pub();
                        $unifiedOrder = new \UnifiedOrder_pub();
                        $unifiedOrder->setParameter("openid","$openid");//商品描述
                        $unifiedOrder->setParameter("body","美硒面膜");//商品描述
//                        //自定义订单号
                        $out_trade_no = $order_info['order_sn'];
                        $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
                        $total_fee=$order_info['pay_price']*100;
                        $unifiedOrder->setParameter("total_fee",$total_fee);//总金额
                        $unifiedOrder->setParameter("notify_url",\weixin1\WxPayConf_pub::NOTIFY_URL);//通知地址
                        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
                        $prepay_id = $unifiedOrder->getPrepayId();
                        $this->logger("prepay_id ".$prepay_id);
                        $jsApi->setPrepayId($prepay_id);
                        $jsApiParameters = $jsApi->getParameters();
                        $this->logger("jsApiParameters ".$jsApiParameters);
                        //日志
                        //触发支付
                        return $jsApiParameters;
                    }elseif($type==2)
                        {
                            Db::startTrans();
                            try {
                                $order_info->order_status=3;
                                $order_info->update_time=date('Y-m-d H:i:s');
                                $order_info->save();
                                // 提交事务
                                Db::commit();
                                return ['code' => 200, 'msg' => '取消成功','url'=>'order/order_list'];
                            } catch (\Exception $e) {
                                //                // 回滚事务
                                Db::rollback();
                                return ['code' => 201, 'msg' => '操作失败,请重试'];
                            }
                        }
                        break;
                case 2:
                    if($type==1)
                    {

                        //退款申请，改状态为7
                        Db::startTrans();
                        try {
                            $order_info->order_status=7;
                            $order_info->update_time=date('Y-m-d H:i:s');
                            $order_info->save();
//
                            $params['order_id']=$order_id;
                            $params['type']=1;
                            $params['reason']=isset($data['reason'])?$data['reason']:'';
                            $params['status']=0;
                            $params['amount']=$order_info->pay_price;
                            $returns->save($params);
                            // 提交事务
                            Db::commit();
                            return ['code' => 200, 'msg' => '申请成功,等待审核','url'=>'order/order_list'];
                        } catch (\Exception $e) {
                            //                // 回滚事务
                            Db::rollback();
                            return ['code' => 201, 'msg' => '操作失败,请重试'];
                        }
                    }
                    break;
                case 4:
                    if($type==1)
                    {
                        $info=$order_e->where('order_id',$order_id)->find();
                        if($info)
                        {
                            $einfo=$express->get($info['express_id']);
                            $code=$einfo->code;
                            $num=$info->express_num;
                            $url="https://m.kuaidi100.com/app/query/?com=".$code."&nu=".$num."&callbackurl=".$_SERVER['HTTP_REFERER'];
                            return ['code' => 200, 'msg' => '正在跳转','url'=>$url];
                        }
                        else
                        {
                            return ['code' => 201, 'msg' => '此订单暂无发货信息'];
                        }
                    }elseif($type==2)
                    {
                        Db::startTrans();
                        try {
                            $order_info->order_status=5;
                            $order_info->update_time=date('Y-m-d H:i:s');
                            $order_info->reveice_time=date('Y-m-d H:i:s');
                            $order_info->need_check_time=date('Y-m-d H:i:s',strtotime("+1 week"));
                            $order_info->save();
                            // 提交事务
                            Db::commit();
                            return ['code' => 200, 'msg' => '签收成功','url'=>'order/order_list'];
                        } catch (\Exception $e) {
                            //                // 回滚事务
                            Db::rollback();
                            return ['code' => 201, 'msg' => '操作失败,请重试'];
                        }
                    }
                    break;
                case 5:
                    if($type==1)
                    {
                        Db::startTrans();
                        try {
                            $order_info->order_status=9;
                            $order_info->update_time=date('Y-m-d H:i:s');
                            $order_info->save();
                            $params['order_id']=$order_id;
                            $params['type']=3;
                            $params['reason']=isset($data['reason'])?$data['reason']:'';
                            $params['amount']=$order_info->pay_price;
                            $returns->save($params);
                            // 提交事务
                            Db::commit();
                            return ['code' => 200, 'msg' => '申请成功,等待审核','url'=>'order/order_list'];
                        } catch (\Exception $e) {
                            //                // 回滚事务
                            Db::rollback();
                            return ['code' => 201, 'msg' => '操作失败,请重试'];
                        }
                    }elseif($type==2)
                    {
                        //退货退款申请
                        Db::startTrans();
                        try {
                            $order_info->order_status=8;
                            $order_info->update_time=date('Y-m-d H:i:s');
                            $order_info->save();
                            $params['order_id']=$order_id;
                            $params['type']=2;
                            $params['reason']=isset($data['reason'])?$data['reason']:'';
                            $params['status']=0;
                            $params['amount']=$order_info->pay_price;
                            $returns->save($params);
                            // 提交事务
                            Db::commit();
                            return ['code' => 200, 'msg' => '申请成功,等待审核','url'=>'order/order_list'];
                        } catch (\Exception $e) {
                            //                // 回滚事务
                            Db::rollback();
                            return ['code' => 201, 'msg' => '操作失败,请重试'];
                        }
                    }
            }
        }
    }

    /*
     *生成订单，订单状态，购物车删除
     * */
    public function wxpay_now(Request $request,MallWechatUser $wuser,MallOrderGoods $o_goods,MallOrders $orders,MallCart $cart)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $order_sn=date('YmdHis',time()).Session::get('wuser_id');
            Db::startTrans();
            try {

                //生成订单
                $order_info['order_sn']=$order_sn;
                $order_info['wuser_id']=Session::get('wuser_id');
                $order_info['user_address']=$datas['user_address'];
                $order_info['total_price']=$datas['total_price'];
                $order_info['pay_price']=$datas['pay_price'];
                $order_info['order_status']=1;
                $order_info['first_buy']=1;
                $order_info['is_check']=2;
                $order_id=$orders->insertGetId($order_info);

                //订单详情
                $cart_arr=explode(',',$datas['cart_ids']);
                foreach ($cart_arr as $k=>$v)
                {
                    $cart_info=$cart->get($v);
                    $order_ginfo['wuser_id']=Session::get('wuser_id');
                    $order_ginfo['order_id']=$order_id;
                    $order_ginfo['good_id']=$cart_info['good_id'];
                    $order_ginfo['good_name']=$cart_info['good_name'];
                    $order_ginfo['good_img']=$cart_info['good_img'];
                    $order_ginfo['good_price']=$cart_info['good_price'];
                    $order_ginfo['good_nums']=$cart_info['good_nums'];
                    $order_ginfo['total_price']=$datas['total_price'];
                    $o_goods->data($order_ginfo,true)->isUpdate(false)->save();
                    //购物车删除
                    $cart_info->delete();
                }
                // 提交事务
                Db::commit();
                //写入日志?
            } catch (\Exception $e) {
                //                // 回滚事务
                Db::rollback();
                return false;
            }

            $openid=$wuser->get(Session::get('wuser_id'))->openid;

            //生成json
            include_once '../extend/weixin1/WxPayPubHelper.php';
            $jsApi = new \JsApi_pub();
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid","$openid");//商品描述
            $unifiedOrder->setParameter("body","美硒面膜");//商品描述
            //自定义订单号
            $out_trade_no = $order_sn;
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $total_fee=$datas['pay_price']*100;
            $unifiedOrder->setParameter("total_fee",$total_fee);//总金额
            $unifiedOrder->setParameter("notify_url",\weixin1\WxPayConf_pub::NOTIFY_URL);//通知地址
            $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
            $prepay_id = $unifiedOrder->getPrepayId();
                $this->logger("prepay_id ".$prepay_id);
            $jsApi->setPrepayId($prepay_id);
//
            $jsApiParameters = $jsApi->getParameters();
            $this->logger("jsApiParameters ".$jsApiParameters);
            //日志

            //触发支付
            return $jsApiParameters;

        }
    }

    /*
     * 验证支付结果
     * 支付
     * */
    public function pay_check(Request $request,MallPayLogs $payLogs,MallOrders $orders)
    {
        include_once '../extend/weixin1/WxPayPubHelper.php';
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = file_get_contents('php://input');

        $this->logger("支付回调".$xml);


        $notify->saveData($xml);
        //获取数组形式的回调数据
        //支付日志paylog
        $order_info=$orders->where('order_sn',$notify->data["out_trade_no"])->find();
        $is_pay=$payLogs->where('order_id',$order_info['id'])->find();
        if($is_pay)
        {
            $pay_info['pay_status']=3;
            $pay_info['transaction_id']=$notify->data["transaction_id"];
            $pay_info['total_price']=$notify->data["total_fee"]/100;
            $pay_info['update_time']=date('Y-m-d H:i:s');
            $is_pay->save($pay_info);
            $pay_id=$is_pay['id'];
        }else
            {
                $pay_info['order_id']=$order_info['id'];
                $pay_info['wuser_id']=$order_info['wuser_id'];
                $pay_info['pay_type']=1;
                $pay_info['pay_status']=3;
                $pay_info['transaction_id']=$notify->data["transaction_id"];
                $pay_info['total_price']=$notify->data["total_fee"]/100;
                $pay_id=$payLogs->insertGetId($pay_info);
            }
        $log = new \log_();
        $log_name="notify_url.log";//log文件路径
        $log->log_result($log_name,"支付回调:\n".$xml."\n");
        //验证签名，并回应微信。
        if($notify->checkSign() == TRUE)
        {
            if($notify->data["return_code"] =='SUCCESS' && $notify->data["result_code"]=='SUCCESS')
            {
                //推送支付完成信息，改订单状态，支付状态
                if($order_info['total_price']*100==$notify->data['total_fee'])
                {
                    Db::startTrans();
                    try {
                        $order_info->order_status=2;
                        $order_info->update_time=date('Y-m-d H:i:s');
                        $order_info->save();
                        $pay=$payLogs->get($pay_id);
                        $pay->time_end=$notify->data['time_end'];
                        $pay->update_time=date('Y-m-d H:i:s');
                        $pay->pay_status=1;
                        $pay->save();
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                       // 回滚事务
                        Db::rollback();
                    }

                    $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
                    $notify->setReturnParameter("return_msg","OK");//返回信息
                    $log->log_result($log_name,"【支付成功】:\n".$xml."\n");
                }else
                {
                    $notify->setReturnParameter("return_code","FAIL");//设置返回码
                    $notify->setReturnParameter("return_msg","金额错误");//返回信息
                    $log->log_result($log_name,"【金额错误】:\n".$xml."\n");
                }
            }else
            {
                $notify->setReturnParameter("return_code","FAIL");//返回状态码
                $notify->setReturnParameter("return_msg",$notify->data["return_msg"]);//返回信息
                //订单状态待支付，支付状态待支付，在待支付链接后更新订单号和下单号
                $log->log_result($log_name,"【出错】:\n".$xml."\n");
            }
        }else
        {
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }

        $returnXml = $notify->returnXml();
        logger("returnXml xml ".$returnXml);
        echo $returnXml;
    }

    public function logger($log_content)
    {
        if(isset($_SERVER['HTTP_BAE_ENV_APPID'])){   //BAE
            include_once "BaeLog.class.php";
            $logger = BaeLog::getInstance();
            $logger ->logDebug($log_content);
        }else if (isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        }else {
            $max_size = 100000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        }
    }

    public function test()
    {
//        include_once '../extend/weixin1/WxPayPubHelper.php';
//        $obj=new \Notify_pub();
//        return $obj->createXml111();

        $a=Db::raw("REPLACE(UUID(),'-','')");
        echo $a;
    }
}