<?php


namespace app\mall\controller;


use alipay\aop\Alije;
use app\common\model\Customers;
use app\common\model\MallDeliveryLogs;
use app\common\model\MallOrderEvaluates;
use app\common\model\MallOrders;
use app\common\model\MallPayLogs;
use app\common\model\MallReceivingAddress;
use app\common\model\MallReturnGood;
use app\common\model\MallShoppingCart;
use app\common\model\MallUserIntegralLogs;
use app\common\model\SystemConfig;
use rsa\Sign;
use think\Controller;
use think\Db;
use wxpay\WeiXinPay;

class Order extends Controller
{
    private $params = [];
    private $user = '';

    protected $beforeActionList = [
        'decrypt'
    ];

    /*前置操作（验签）*/
    protected function decrypt()
    {
        $sign = new Sign();
        $res = $sign->create_sign();
        if ($res['code']==201){
            return $res;
        }
        $this->params = $res['params'];

        $params = $res['params'];
        $user = Customers::field('id,is_supplier,is_agent,cus_name,integral')
            ->where(['token' => $params['token']])
            ->find();
        if (empty($user)){
            return err_return(lang('login_info_err'));
        }
        $this->user = $user;

    }

    /*用户订单列表
    type：订单类型，待支付，待发货，配送中，待评价，取消订单
    page： 分页
    */
    public function user_orders(MallOrders $orders,MallReceivingAddress $mallReceivingAddress,MallDeliveryLogs $mallDeliveryLogs){
        $user = $this->user;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $type = isset($params['type']) ? $params['type'] : 'all';
        switch($type){
            case 'wait_pay':
                $scope = 'waitpay';
                break;
            case 'wait_deliver':
                $scope = 'waitdeliver';
                break;
            case 'delivering':
                $scope = 'delivering';
                break;
            case 'wait_comment':
                $scope = 'waitcomment';
                break;
            case 'cancel':
                $scope = 'cancel';
                break;
            default:
                $scope = 'waitpay';
        }
        $field = "id,order_price,integral,actual_price,supplier_id,order_status,rec_address,is_urgent";

        if ($type=='all'){
            $user_orders = $orders->with(['supplier' =>function($query){
                $query->field('id,sup_name');
            }])->where(['customer_id' => $user['id']])
                ->field($field)
                ->page($page,3)
                ->order(['create_time' => 'desc'])
                ->select();
        }else{
            $user_orders = $orders->with(['supplier' =>function($query){
                $query->field('id,sup_name');
            }])->scope($scope)->field($field)
                ->where(['customer_id' => $user['id']])
                ->page($page,3)
                ->order(['create_time' => 'desc'])
                ->select();
        }

//        订单商品
        if (!$user_orders->isEmpty()){
            $order_to_deli = array();
            if ($type=='delivering'){
                $order_ids = array_column($user_orders->toArray(),'id');
                $deliveries = $mallDeliveryLogs->whereIn('order_id',$order_ids)
                    ->field('status,deliverier_name,deliverier_tel,order_id')
                    ->select();
                foreach($deliveries as $key){
                    $order_to_deli['o'.$key['order_id']] = $key;
                }
                unset($key);
            }else{
                $order_to_deli = array();
            }

            foreach($user_orders as $key =>$val){
                $table_pre = 'mall_order_goods_';
                $prefix = $val['id']%10==0 ? 10 :  $val['id']%10;
                $goods = Db::table($table_pre.$prefix)
                    ->field('good_img,supplier_name,good_specs,good_price,good_nums,good_amount,order_id,good_name')
                    ->where(['order_id' => $val['id']])
                    ->select();
//                if (empty($goods)){
//                    unset($user_orders[$key]);
//                    continue;
//                }
                $user_orders[$key]['goods'] = $goods;
                $rec_addr = $mallReceivingAddress->get($val['rec_address']);
                if (!empty($rec_addr)){
                    $user_orders[$key]['rec_addr'] = $rec_addr->dizhi;
                }else{
                    $user_orders[$key]['rec_addr'] = '';
                }
                if (isset($order_to_deli['o'.$val['id']])){
                    $user_orders[$key]['deliverier_name'] = $order_to_deli['o'.$val['id']]['deliverier_name'];
                    $user_orders[$key]['deliverier_tel'] = $order_to_deli['o'.$val['id']]['deliverier_tel'];
                    $user_orders[$key]['deliverier_status'] = $order_to_deli['o'.$val['id']]['status'];
                }else{
                    $user_orders[$key]['deliverier_name'] = '';
                    $user_orders[$key]['deliverier_tel'] = '';
                    $user_orders[$key]['deliverier_status'] = '';
                }
            }
            unset($key);
            unset($val);
            return right_return($user_orders->toArray()
            );
        }else{
            return err_return(lang('no_more'));
        }

    }

    /*订单统计*/
    public function orders_count(MallOrders $orders){
        $user = $this->user;
        $dans = $orders->field('count(*) as zong,order_status')
            ->where(['customer_id' => $user['id']])
            ->group('order_status')
            ->select();
        $hui = array();
        foreach ($dans as $key=>$val){
            $hui['k'.$val['order_status']] = $val['zong'];
        }
        unset($key);
        unset($val);
        return right_return($hui);
    }

    /*用户下单
     * 1：根据供应商不同生成多个购物订单，统计价钱:2：最后删除购物车商品，3：给供应商推送消息
     * $use_id 用户id
     * $goods 要结算的商品
     * $addr_id 收货地址
     * $remark 订单备注
    */
    public function create_order(MallOrders $orders, MallShoppingCart $cart,MallUserIntegralLogs $mallUserIntegralLogs,SystemConfig $systemConfig){
        $params = $this->params;
        $user = $this->user;
        $user_integral = $user['integral'];
        $receive_addr_id = $params['addr_id'];
        $remark = $params['remark'];
        $goods = $params['goods'];//选定的商品的购物车信息
        $goods = explode('|',$goods);
        $goods = array_map(function($value){
            $value = str_replace('\\','/',$value);
            return json_decode($value,true);
        },$goods);
        //加急单
        $is_urgent = $params['is_urgent']=='false' ? false : true;
        //区分商家
        $suppliers = array_column($goods,'supplier_id');
        $suppliers = array_unique($suppliers);
        $order_goods = array();
        foreach($suppliers as $key => $value){
            $ii = 0;
            $order_goods[$key]['sum_price'] = 0;
            $order_goods[$key]['supp_id'] = $value;
            foreach ($goods as $kg => $vg){
                if ($value==$vg['supplier_id']){
                    $order_goods[$key]['sum_price'] += $vg['good_price']*$vg['nums'];
                    $order_goods[$key]['goods'][$ii] = $vg;
                    $ii++;
                    unset($goods[$kg]);
                }
            }
            unset($kg);
            unset($vg);
        }
        unset($key);
        unset($value);

//        根据不同的供应商生成不同的订单
        $ofail = 0;
        $all_price = 0;
        $all_integral = 0;
        $order_ids = array();
        //系统设置的积分扣除比例
        $mobile_app = $systemConfig->getFieldByType('mobile_app','content');
        $mobile_app = json_decode($mobile_app,true);
        $balance_deduct = $mobile_app['balance']/100;

        foreach ($order_goods as $key => $value){
            //积分抵扣
            $deduct_integral = floor($value['sum_price']*$balance_deduct);//将要扣除的积分
            if ($user_integral > 0){
                //如果将要扣除的积分比用户的积分多，值扣除用户有的积分
                if ($deduct_integral > $user_integral){
                    $deduct_integral = $user_integral;
                    $user_integral = 0;
                    $user->integral = 0;
                }else{
                    $user_integral -= $deduct_integral;
                    $user->integral = ['dec',$deduct_integral];
                }
                $user->save();
            }else{
                $deduct_integral = 0;
            }

            $order_content = array();
            $order_content['order_sn'] = date('YmdHis').rand(100,999).$key;
            $order_content['customer_id'] = $user['id'];
            $order_content['customer_name'] = $user['cus_name'];
            $order_content['order_price'] = $value['sum_price'];
            $order_content['integral'] = $deduct_integral;
            $order_content['actual_price'] = $value['sum_price'] - $order_content['integral'];
            $order_content['supplier_id'] = $value['supp_id'];
            $order_content['supplier_name'] = $value['goods'][0]['supplier_name'];
            $order_content['rec_address'] = $receive_addr_id;
            $order_content['remark'] = $remark;
            $order_content['is_urgent'] = $is_urgent;

//            生成订单
            if($ding = $orders->create($order_content)){
                $order_id = $ding->id;
                $order_ids[] = $order_id;
                $inegral_log = array();
                if($deduct_integral > 0){
                    $inegral_log['customer_id'] = $user['id'];
                    $inegral_log['order_id'] = $order_id;
                    $inegral_log['type'] = 1;
                    $inegral_log['create_time'] = date('Y-m-d H:i:s');
                    $inegral_log['integral'] = $deduct_integral;
                    $mallUserIntegralLogs->save($inegral_log);
                }
//                向订单商品表中插入商品
                foreach ($value['goods'] as $k => $v){
                    $og = array();
                    $og['order_id'] = $order_id;
                    $og['customer_id'] = $user['id'];
                    $og['good_id'] = $v['good_id'];
                    $og['good_name'] = $v['good_name'];
                    $og['good_img'] = $v['good_img'];
                    $og['good_price'] = $v['good_price'];
                    $og['good_nums'] = $v['nums'];
                    $og['good_specs'] = $v['good_specs'];
                    $og['good_amount'] = $v['nums']*$v['good_price'];
                    $og['supplier_id'] = $v['supplier_id'];
                    $og['supplier_name'] = $v['supplier_name'];
                    $og['create_time'] = date('Y-m-d H:i:s');
                    $suffix = $order_id%10 == 0 ? 10 : $order_id%10;
                    $table_name = 'mall_order_goods_'.$suffix;
                    $res = Db::table($table_name)->data($og)->insert();
                    if ($res){
                        $all_price += $order_content['actual_price'];
                        $all_integral += $order_content['integral'];
                        //订单生成成功，订单产品保存成功，删除购物车的保存
                        $cart->where(['customer_id' =>$user['id'], 'good_id' =>$v['good_id']])->delete();
                    }
                }
            }else{
//                订单生成失败
                $ofail++;
            }
        }

        if ($ofail==0){
            //返回实际付款金额
            $actual_all_price = $all_price - floor($all_price*$balance_deduct);
            return right_return(['actual_price' => $actual_all_price, 'orders' => implode($order_ids,'-'),'integral'=>$all_integral],lang('xiadan_ok'));
        }else{
            if ($all_price==0){
                return err_return(lang('xiadan_fail'));
            }else{
                //返回实际付款金额
                $actual_all_price = $all_price - floor($all_price*$balance_deduct);
                return right_return(['actual_price' => $actual_all_price, 'orders' => implode($order_ids,'-'),'integral'=>$all_integral],lang('xiadan_ok'));
            }
        }
    }

    /*取消订单
     * 只有未付款的订单才能取消，付款订单就是退款了
     * $order_id 订单id
     * $user 用户
    */
    public function cancel_order(MallOrders $mallOrders){
        $user = $this->user;
        $params = $this->params;
        $order_id = $params['order_id'];
        $order = $mallOrders->get($order_id);
        if (empty($order)){
            return err_return(lang(''));
        }else{
            if ($user['id']!=$order['customer_id']){
                return err_return(lang('not_action_other'));
            }
            if ($order['order_status']!=1){
                return err_return(lang('err_order_status'));
            }else{
                $order->order_status = 3;
                if ($order->save()){
                    //返回用户扣除的积分
                    if ($order['integral'] > 0){
                        $user->integral = ['inc',$order['integral']];
                        $user->save();
                    }
                    return right_return([],lang('cancel_ok'));
                }else{
                    return err_return(lang('action_err'));
                }
            }
        }
    }

    /*订单评价
     * 保存评论信息，并修改订单状态
     * $order_id 订单id
     * $to_good 对商品打分
     * $to_supplier 对供应商打分
     * $to_delivery 对配送打分
     * $remark 留言
     * $images 图片
    */
    public function evalute_order(MallOrderEvaluates $orderEvaluates,MallOrders $mallOrders){
        $params = $this->params;
        $user = $this->user;
        $order = $mallOrders->get($params['order_id']);
        if (empty($order)){
            return err_return(lang('err_order'));
        }
        if ($order['order_status'] ==6){
            return err_return(lang('repeat_evaluate'));
        }
        $order->order_status = 6;
        if ($order->save()){
            //处理上传上来的图片
            $images = [];
            if (!empty($params['images'])){
                $images = explode('|',$params['images']);
                $images = array_map(function($v){
                    return $this->handle_image($v,ROOT_PATH.'/public/images/order/');
                },$images);
            }
            $images = json_encode($images);
            $evaluate = array();
            $evaluate['customer_id'] = $user['id'];
            $evaluate['cus_name'] = $user['cus_name'];
            $evaluate['to_good'] = $params['to_good'];
            $evaluate['to_supplier'] = $params['to_supplier'];
            $evaluate['to_delivery'] = $params['to_delivery'];
            $evaluate['images'] = $images;
            $evaluate['remark'] = $params['remark'];
            $evaluate['order_id'] = $params['order_id'];
            if ($orderEvaluates->save($evaluate)){

                return right_return([],lang('evaluate_ok'));
            }else{
                return err_return(lang('action_err'));
            }
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 退货
     * 判断订单状态，签收的订单才能退货，修改订单状态，记录退款原因
     * $order_id 订单id
     * $user 用户
     * */
    public function return_good(MallOrders $mallOrders,MallReturnGood $mallReturnGood){
        $params = $this->params;
        $user = $this->user;
        
        $order = $mallOrders->get($params['order_id']);
        if (empty($order)){
            return err_return(lang('err_order'));
        }

        if ($order['order_status']==7){
            return err_return(lang('repeat_return'));
        }

        if ($params['type']==1){
            if ($order['order_status']!=2){
                return err_return(lang('not_return'));
            }
        }else{
            if ($order['order_status']!=4||$order['order_status']!=5){
                return err_return(lang('not_return'));
            }
        }

        $order->order_status = 7;
        if ($order->save()){
            //处理上传的图片
            $images = [];
            if (!empty($params['images'])){
                $images = explode('|',$params['images']);
                $images = array_map(function($v){
                    return $this->handle_image($v,ROOT_PATH.'/public/images/order/');
                },$images);
            }
            $images = json_encode($images);
            //记录退货原因
            $re_good = array();
            $re_good['customer_id'] = $user['id'];
            $re_good['order_id'] = $order['id'];
            $re_good['type'] = $params['type'];
            $re_good['reason'] = $params['reason'];
            $re_good['amount'] = $order['actual_price'];
            $re_good['integral'] = $order['integral'];
            $re_good['remark'] = $order['remark'];
            $re_good['images'] = $images;
            if ($mallReturnGood->save($re_good)){
                return right_return([],lang('regood_success'));
            }else{
                return err_return(lang('action_err'));
            }
        }else{
            return err_return(lang('action_err'));
        }

    }

    /* 订单签收
     * 不需要管配送状态
     *
     * */
    public function sign_for_order(MallOrders $mallOrders){
        $params = $this->params;
        $user = $this->user;
        $order = $mallOrders->get($params['order_id']);
        if (empty($order)){
            return err_return(lang('err_order'));
        }
//        $delivery = $order->delivery;
//        if ($delivery['status']!=2){
//            return err_return(lang());
//        }
        $order->order_status = 5;
        if ($order->save()){
            return right_return([],lang('sign_for_ok'));
        }else{
            return err_return(lang('action_err'));
        }

    }


    /* 配送完成
     * 修改配送表中的状态
     *  */
    public function delivery_done(MallDeliveryLogs $mallDeliveryLogs){
        $params = $this->params;
        $user = $this->user;
        $delivery = $mallDeliveryLogs->where(['order_id' => $params['order_id']])->find();
        if ($delivery['status']==2){
            return err_return(lang('repeat_delivery'));
        }
        $delivery->status = 2;
        if ($delivery->save()){
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }

    }

    /* 生成支付订单
     * 根据支付方式，生成不同的支付订单
     * */
    public function pay_order(Alije $alije,WeiXinPay $weiXinPay,MallPayLogs $mallPayLogs){
        $params = $this->params;
        $payment = $params['payment'];
        $logs = array();
        $logs['order_id'] = $params['oid'];
        $logs['total_amount'] = $params['actual_price'];
        $logs['pay_way'] = $payment== 'alipay' ? 2 : 1;

        if (empty($pay_logs)){
            $logs['customer_id'] = $this->user->id;
            if ($mallPayLogs->save($logs)){
                $log_id = $mallPayLogs->id;
                switch ($payment){
                    case 'alipay':
                        $res = $alije->create_request($log_id,$params['actual_price'],'',$params['oid']);
                        break;
                    case 'wxpay':
                        $res = $weiXinPay->create_order('goods',$params['oid'],$log_id,$params['actual_price']);
                        break;
                    default :
                        return err_return(lang('no_payment'));
                        break;
                }
                return right_return($res);
            }else{
                return err_return(lang('err_pay_order'));
            }
        }else{
            if ($pay_logs['pay_status']==1){
                return err_return(lang('pay_finish'));
            }else{
                $logs['customer_id'] = $this->user->id;
                if ($mallPayLogs->save($logs)){
                    $log_id = $mallPayLogs->id;
                    switch ($payment){
                        case 'alipay':
                            $res = $alije->create_request($log_id,$params['actual_price'],'',$params['oid']);
                            break;
                        case 'wxpay':
                            $res = $weiXinPay->create_order('goods',$params['oid'],$log_id,$params['actual_price']);
                            break;
                        default :
                            return err_return(lang('no_payment'));
                            break;
                    }
                    return right_return($res);
                }
            }
        }
    }

    /*处理base64的图片*/
    protected function handle_image($str_base64,$saveDirectory){
//        $str_base64 = urldecode($str_base64);
        //测试数据
        $str_base64 = str_replace('data:image/png;base64,', '', $str_base64);
        $str_base64 = str_replace('data:image/jpg;base64,', '', $str_base64);
        $str_base64 = str_replace('data:image/jpeg;base64,', '', $str_base64);
        $str_base64 = str_replace('data:image/gif;base64,', '', $str_base64);
        $str_base64 = str_replace('data:image/ico;base64,', '', $str_base64);
        if(empty($str_base64))
        {
            return '';
        }
        //解码base64字符串
        $str_base64 = base64_decode($str_base64);
        $tmp_name = time().rand(100,999);
        $file_ext='.jpg';
        $filename_result=$tmp_name.$file_ext;
        if(!file_exists($saveDirectory)){
            mkdir($saveDirectory,0777,true);
        }
        //以图片格式保存至本地
        file_put_contents($saveDirectory.$filename_result, $str_base64);
        return str_replace(ROOT_PATH.'/public/','',$saveDirectory.$filename_result);
    }



}