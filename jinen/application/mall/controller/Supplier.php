<?php


namespace app\mall\controller;


use app\common\model\Customers;
use app\common\model\MallCashOutLogs;
use app\common\model\MallDeliveryLogs;
use app\common\model\MallGoods;
use app\common\model\MallOrderEvaluates;
use app\common\model\MallOrders;
use app\common\model\MallReceivingAddress;
use app\common\model\MallReturnGood;
use app\common\model\MallSupBalanceLogs;
use app\common\model\MallSuppliers;
use app\common\model\SystemConfig;
use rsa\Sign;
use think\Controller;
use think\Db;

class Supplier extends Controller
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
        $user = Customers::field('id,is_supplier,is_agent')
            ->where(['token' => $params['token']])
            ->with('sup')
            ->find();
        if (empty($user)){
            return err_return(lang('login_info_err'));
        }
        $this->user = $user;

    }

    /* 供应商申请
     * $sup_name 供应商店名
     * $real_name 供应商实名制认证
     * $province,$city,$area,$detail_address 省市区详细地址
     * $id_card 身份证号码
     * $business_license 营业执照
     * */
    public function apply(MallSuppliers $mallSuppliers){
        $user =$this->user;
        $params = $this->params;
        if ($params['is_edit']=='true'){
            //审核失败后，修改用户信息，继续审核
            $supplier = $this->user->sup;
            $supplier->sup_name = $params['shop_name'];
            $supplier->real_name = $params['apply_name'];
            $supplier->tel = $params['apply_phone'];
            $supplier->province = $params['province'];
            $supplier->city = $params['city'];
            $supplier->area = $params['area'];
            $supplier->detail_addr = $params['detail_address'];
            $supplier->id_card = $params['id_card'];
            $supplier->status = 3;
            if (!strpos($params['idzheng'],'images/supplier')){
                $supplier->id_card_front = $this->handle_image($params['idzheng'],ROOT_PATH.'/public/images/supplier/');
            }
            if (!strpos($params['idfan'],'images/supplier')){
                $supplier->id_card_back = $this->handle_image($params['idfan'],ROOT_PATH.'/public/images/supplier/');
            }
            if (!strpos($params['zhizhao'],'images/supplier')){
                $supplier->business_license = $this->handle_image($params['zhizhao'],ROOT_PATH.'/public/images/supplier/');
            }
            if ($supplier->save()){
                return right_return([],lang('apply_success'));
            }else{
                return err_return(lang('action_err'));
            }

        }else{
            //添加申请商户
            $info = array();
            $info['sup_name'] = $params['shop_name'];
            $info['real_name'] = $params['apply_name'];
            $info['tel'] = $params['apply_phone'];
            $info['province'] = $params['province'];
            $info['city'] = $params['city'];
            $info['area'] = $params['area'];
            $info['detail_addr'] = $params['detail_address'];
            $info['id_card'] = $params['id_card'];
            $info['id_card_front'] = $this->handle_image($params['idzheng'],ROOT_PATH.'/public/images/supplier/');
            $info['id_card_back'] = $this->handle_image($params['idfan'],ROOT_PATH.'/public/images/supplier/');
            $info['business_license'] = $this->handle_image($params['zhizhao'],ROOT_PATH.'/public/images/supplier/');
            $info['customer_id'] = $user['id'];
            if ($mallSuppliers->save($info)){
                return right_return([],lang('apply_success'));
            }else{
                return err_return(lang('action_err'));
            }
        }

    }

    /* 供应商信息
     *
     * */
    public function get_supplier_info(MallSuppliers $mallSuppliers){
        $user = $this->user;
        $params = $this->params;
        $supp = $mallSuppliers->where(['customer_id' => $user['id']])->find();
        if (empty($supp)){
            return err_return('');
        }
        $supplier = $this->user->sup;
        if (isset($params['source'])){
            $sup_info = $supplier->append(['dizhi']);
        }else{
            $sup_info = $supplier->visible(['sup_name']);
        }

        if (!empty($sup_info)){
            return right_return($sup_info);
        }else{
            return err_return(lang('err_supplier_info'));
        }

    }

    /* 供应商商品
     * 分页显示供应商的所有商品
     * */
    public function sup_good_list(MallGoods $mallGoods){
        $supplier = $this->user->sup;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = 8;
        $goods = $mallGoods->where(['supplier_id' => $supplier['id'],'is_delete' => 0])
            ->field('id,good_name,price,good_img,specs,unit,is_sale,is_delete,is_check')
            ->page($page,$limit)
            ->order(['create_time' => 'desc'])
            ->all();
        return right_return($goods);

    }

    /* 商品上下架
     * $good_id 商品id
     * */
    public function good_sale(MallGoods $mallGoods){
        $params = $this->params;
        $supplier = $this->user->sup;
        $good = $mallGoods->get($params['good_id']);
        if (empty($good)){
            return err_return(lang('empty_good'));
        }
        if ($good['supplier_id']!=$supplier['id']){
            return err_return(lang('not_supplier'));
        }
        $good->is_sale = $good->is_sale == 1 ? 0 : 1;
        if ($good->save()){
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 删除商品
     * */
    public function del_good(MallGoods $mallGoods){
        $params = $this->params;
        $supplier = $this->user->sup;
        $good = $mallGoods->get($params['good_id']);
        if (empty($good)){
            return err_return(lang('empty_good'));
        }
        if ($good['supplier_id']!=$supplier['id']){
            return err_return(lang('not_supplier'));
        }
        $good->is_delete = 1;
        if ($good->save()){
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 供应商订单
     * */
    public function sup_order(MallOrders $mallOrders,MallReturnGood $mallReturnGood){
        $params = $this->params;
        $supplier = $this->user->sup;
        $status = isset($params['status']) ? $params['status'] : 2;
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = 3;
        if ($status==3){
            $orders = $mallOrders->where(['mall_orders.supplier_id' => $supplier['id'],'mall_orders.order_status' => $status])
                ->withJoin(['refundg'=>function($query){
                    $query->where(['refundg.progress' => 1])->withField('order_id,progress');
                }])
                ->field('mall_orders.id,mall_orders.customer_name,mall_orders.order_status')
                ->page($page,$limit)
                ->order(['create_time' => 'desc'])
                ->all();
        }else{
            $orders = $mallOrders->where(['supplier_id' => $supplier['id'],'order_status' => $status])
                ->field('id,customer_name,order_status')
                ->page($page,$limit)
                ->order(['create_time' => 'desc'])
                ->all();
        }
        if ($orders->isEmpty()){
            return err_return('');
        }
        //对于退款的订单，分开没有处理的

        foreach ($orders as $key =>$value){
            $suffix = $value['id']%10==0 ? 10 : $value['id']%10;
            $table = 'mall_order_goods_'.$suffix;
            $goods = Db::table($table)
                ->where(['order_id' => $value['id']])
                ->field('good_name,good_img,good_price,good_nums,good_specs')
                ->select();
            $orders[$key]['goods'] = $goods;
        }
        unset($key);
        unset($value);
        return right_return($orders);
    }

    /* 供应商订单出货
     * 修改订单状态，查找配送员，配送表加入数据
     * */
    public function shipment(MallOrders $mallOrders,MallReceivingAddress $mallReceivingAddress, MallDeliveryLogs $mallDeliveryLogs){
        $params = $this->params;
        $supplier = $this->user->sup;
        $order = $mallOrders->get($params['order_id']);
        if (empty($order)){
            return err_return(lang('err_order'));
        }
        if ($order['supplier_id']!=$supplier['id']){
            return err_return(lang('not_action_other'));
        }
        //修改订单状态
        $order->order_status = 4;
        if ($order->save()){
            //配送员还有待商榷

            //配送表中插入数据
            $rec_addr = $mallReceivingAddress->get($order['rec_address']);//收货地址相关信息
            $delivery = array();
            $delivery['order_id'] = $order['id'];
            $delivery['province'] = $rec_addr['province'];
            $delivery['city'] = $rec_addr['city'];
            $delivery['area'] = $rec_addr['area'];
            $delivery['detail_address'] = $rec_addr['detail_address'];
            $delivery['customer_id'] = $rec_addr['customer_id'];
            $delivery['customer_tel'] = $rec_addr['receiver_phone'];
            $delivery['customer_name'] = $rec_addr['receiver_name'];
            //还缺少配送员相关信息
            if ($mallDeliveryLogs->save($delivery)){
                return right_return([],lang('action_ok'));
            }else{
                return err_return(lang('action_err'));
            }
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 订单退款
     * 订单相关检测无误后，退款，退款成功后怎么搞
     * */
    public function refund_order(){

    }

    /* 商户提现申请
     * 提交申请后先扣钱，记录商户提现申请，并记录余额变动流水
     *
     * */
    public function supp_cash_out(MallCashOutLogs $mallCashOutLogs,MallSupBalanceLogs $mallSupBalanceLogs,SystemConfig $systemConfig){
        $supplier = $this->user->sup;
        $params = $this->params;
        //先判断余额是否充足
        if ($params['cash'] > $supplier['balance']){
            return err_return(lang('balance_not_enough'));
        }
        //系统设置的积分扣除比例
        $mobile_app = $systemConfig->getFieldByType('mobile_app','content');
        $mobile_app = json_decode($mobile_app,true);
        //手续费比例
        $proportion = $mobile_app['commission'];

        $cash_out = array();
        $cash_out['account'] = $params['account'];
        $cash_out['account_name'] = $params['account_name'];
        $cash_out['account_bank'] = $params['account_bank'];
        $cash_out['cash'] = $params['cash'];
        $cash_out['commission'] = floor($params['cash']*$proportion)/100;
        $cash_out['actual_cash'] = $params['cash'] - $cash_out['commission'];
        $cash_out['supplier_id'] = $supplier['id'];
        if ($mallCashOutLogs->save($cash_out)){
            //扣除商户的余额
            $supplier->balance = ['dec',$params['cash']];
            if ($supplier->save()){
                //记录商户的余额变动记录
                $logs = array();
                $logs['cash_out_id'] = $mallCashOutLogs->id;
                $logs['type'] = 2;
                $logs['balance'] = $params['cash'];
                $logs['supplier_id'] = $supplier['id'];
                if ($mallSupBalanceLogs->save($logs)){
                    return right_return([],lang('cash_out_ok'));
                }else{
                    return err_return(lang('action_err'));
                }
            }else{
                return err_return(lang('action_err'));
            }
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 商户余额变动流水
     * */
    public function supp_balance_logs(MallSupBalanceLogs $mallSupBalanceLogs,MallCashOutLogs $mallCashOutLogs){
        $supplier = $this->user->sup;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = 10;
        $logs = $mallSupBalanceLogs->where(['supplier_id' => $supplier['id']])
            ->order(['create_time' => 'desc'])
            ->page($page,$limit)
            ->all();
        $sums = $mallSupBalanceLogs->where(['supplier_id' => $supplier['id']])
            ->column('sum(balance) as zong','type');

        if ($logs->isEmpty()){
            return err_return('');
        }else{
            $cash_out_logs = array_filter($logs->toArray(),function ($value){
                return !empty($value['cash_out_id']);
            });
            $cashes = array();
            if (!empty($cash_out_logs)){
                $cash_out_ids = array_column($cash_out_logs,'cash_out_id');
                $cashes = $mallCashOutLogs->whereIn('id',$cash_out_ids)
                    ->column('progress','id');
            }

            foreach ($logs as $key => $log){
                if ($log['type']!=1){
                    $logs[$key]['progress'] = $cashes[$log['cash_out_id']];
                }else{
                    $logs[$key]['progress'] = '';
                }
            }

            return right_return(['logs' => $logs, 'balance' => $supplier['balance'], 'sums' => $sums]);
        }
    }

    /* 提现详情
     * $cash_out_id 提现流水id
     * */
    public function cash_out_detail(MallCashOutLogs $mallCashOutLogs){
        $params = $this->params;
        $cash_out_detail = $mallCashOutLogs->get($params['cash_out_id']);
        if (empty($cash_out_detail)){
            return err_return(lang('err_cash_out'));
        }else{
            return right_return($cash_out_detail);
        }
    }

    /*处理base64的图片*/
    protected function handle_image($str_base64,$saveDirectory){
//        $str_base64 = urldecode($str_base64);
        //测试数据
        $str_base64=str_replace('data:image/png;base64,', '', $str_base64);
        $str_base64=str_replace('data:image/jpg;base64,', '', $str_base64);
        $str_base64=str_replace('data:image/jpeg;base64,', '', $str_base64);
        $str_base64=str_replace('data:image/gif;base64,', '', $str_base64);
        $str_base64=str_replace('data:image/ico;base64,', '', $str_base64);
        if(empty($str_base64))
        {
            return '';
        }
        //解码base64字符串
        $str_base64 = base64_decode($str_base64);
        $tmp_name = time().rand(100,999);
        $file_ext='.jpg';
        $filename_result = $tmp_name.$file_ext;
        if(!file_exists($saveDirectory)){
            mkdir($saveDirectory,0777,true);
        }
        //以图片格式保存至本地
        file_put_contents($saveDirectory.$filename_result, $str_base64);
        return str_replace(ROOT_PATH.'/public/','',$saveDirectory.$filename_result);
    }


}