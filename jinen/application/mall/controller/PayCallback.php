<?php


namespace app\mall\controller;

use alipay\aop\Alije;
use app\common\model\MallOrders;
use app\common\model\MallPayLogs;
use app\common\model\MallSuppliers;
use think\Controller;
use think\facade\Request;

class PayCallback extends Controller
{
    /* 支付宝支付回调
     * 修改订单状态等相关操作，记录交易流水，通知供应商
     * */
    public function ali_back(){
        $params = Request::post();
        $alipay = new Alije();
        $res = $alipay->verfiy_sign($params);
        if ($res){
            //支付成功
            if($params['trade_status']=='TRADE_SUCCESS') {
                $log = MallPayLogs::get($params['out_trade_no']);
                if ($log['pay_status']==1){
                    exit('success');
                }
                //修改支付流水表状态
                $log->pay_status = 1;
                $log->trade_no = $params['trade_no'];
                $log->save();

                $res = $this->order_action($params['passback_params']);
                if ($res){
                    exit('success');
                }
            }
        }
    }

    /*微信支付回调*/
    public function weixin_back(){
        $testxml  = file_get_contents("php://input");  //接收微信发送的支付成功信息
        //将XML转换成数组
        $datas = json_decode(json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($datas['return_code']=='SUCCESS' && $datas['result_code']=='SUCCESS'){
            ksort($datas);
            $string = "";
            foreach ($datas as $k => $v)
            {
                if($k != "sign" && $v != "" && !is_array($v)){
                    $string .= $k . "=" . $v . "&";
                }
            }
            $string = trim($string, "&");
            //签名步骤二：在string后加入KEY
            $string = $string . "&key=".'1b1c0eaaecf2cc94ee755dc5c2344827';
            //签名步骤三：MD5加密或者HMAC-SHA256
            //是用sha256校验
            $xmlSign = hash_hmac("sha256",$string ,'60616c4dadda80343d48a4424881a5e4');
            $xmlSign=strtoupper($xmlSign);
            //验证加密后的32位值和 微信返回的sign 是否一致！！！
            if ( $datas['sign'] == $xmlSign) {
                $log = MallPayLogs::get($datas['out_trade_no']);
                if ($log['pay_status']==1){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
                }
                //
                $log->pay_status = 1;
                $log->transaction_id = $datas['transaction_id'];
                $log->save();
                $res = $this->order_action($datas['attach']);
                if ($res){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
                }
            }
        }
    }

    /* 订单的相关操作
     * 检测订单状态，如果已经支付，直接返回，如果未支付，修改订单状态，给商户添加余额
     * */
    private function order_action($oid){
        $order_id = explode('-',$oid);
        $orders = MallOrders::whereIn('id',$order_id)->where(['order_status' => 1])->all();
        if (!$orders->isEmpty()){
            $res_nums = count($orders);
            foreach ($orders as $order){
                //检测订单状态
                if ($order['order_status']!=1){
                    continue;
                }
                //修改订单状态
                $order->order_status = 2;
                $order->save();
                //商户添加余额
                $supplier = MallSuppliers::get($order['supplier_id']);
                $supplier->balance = ['inc',$order['order_price']];
                $supplier->save();
                $res_nums--;
                //商户余额变动流水

            }
            if ($res_nums==0){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }

    }


}