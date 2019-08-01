<?php


namespace app\mall\controller;


use aliyun\SendSms;
use app\common\model\Customers;
use app\common\model\MallDeliveryLogs;
use app\common\model\MallOrderEvaluates;
use app\common\model\MallReceivingAddress;
use app\common\model\MallUserIntegralLogs;
use app\common\model\Region;
use phpexcel\DownXlsx;
use think\Controller;
use rsa\Sign;
use app\common\model\ShortMessage;
use think\Db;

class User extends Controller
{
    private $params = [];
    private $user = '';

    protected $beforeActionList = [
        'decrypt',
        'token_to_user' =>  ['except'=>'register,login,get_phone_code'],
    ];

    /*前置操作（验签）*/
    protected function decrypt()
    {
        $sign = new Sign();
        $res = $sign->create_sign();
        if ($res['code']!=200){
            return err_return($res['msg']);
        }
        $this->params = $res['params'];
    }

    /*前置操作（根据token得到用户）*/
    public function token_to_user(){
        $params = $this->params;
        $user = Customers::field('id,headimgurl,shop_name,phone,province,city,area,role,is_agent,is_supplier,token,integral,detail_address,default_rec_address,cus_name,password,salt,client_id,invite_img')
            ->where(['token' => $params['token']])
            ->find();
        if (empty($user)){
            return err_return(lang('login_info_err'));
        }
        $this->user = $user;
    }

    //用户注册
    /*phone，电话号码
    password：密码（已经加密）
    code：验证码
    invite_code: 加密邀请码*/
    public function register(Sign $sign,ShortMessage $message,Customers $customers){
        $params = $this->params;
        $result = $this->validate($params,'app\mall\validate\RegUser');
        if (true !== $result) {
            // 验证失败 输出错误信息
            return err_return($result);
        }
        //验证码
        $shortmsg = $message->getByPhone($params['phone']);
        if ($shortmsg['code']!=$params['code']){
            return err_return(lang('code_err'));
        }
        if ($shortmsg['expire_time'] < time()){
            return err_return(lang('expired_time'));
        }
        //电话号码不能重复
        $rep_custom = $customers->where(['phone' => $params['phone']])->find();
        if (!empty($rep_custom)){
            return err_return(lang('repeat_phone'));
        }

        //邀请码（一个加密字符串）
        $invite_code = $sign->rsaDecrypt($params['invite_code']);
        if (empty($invite_code)){
            return err_return(lang('invite_code_err'));
        }
        parse_str($invite_code,$invite_codes);
        $params['province'] = $invite_codes['province'];
        $params['city'] = $invite_codes['city'];
        $params['area'] = $invite_codes['area'];
        $params['salesman'] = $invite_codes['user_id'];

        //验证成功，插入用户
        unset($params['code']);
        unset($params['invite_code']);
        $params['salt'] = rand(1000,9999);
        $params['password'] = md5($params['password'].$params['salt']);
        $params['create_time'] = date('Y-m-d H:i:s');
        $params['token'] = Db::raw("REPLACE(UUID(),'-','')");
        if ($customers->save($params)){
            $id = $customers->id;
            $message->where(['id' => $shortmsg['id']])->delete();
            $user = $customers->field('headimgurl,shop_name,phone,province,city,area,role,is_agent,is_supplier,token,integral,detail_address')->get($id);
            $user['headimgurl'] = HOST_NAME.$user['headimgurl'];
            $user['dizhi'] = $user->dizhi;
            return right_return($user);
        }else{
            return err_return(lang('action_err'));
        }

    }

    /*用户登录
        phone: 电话号码
        password: 密码
    */
    public function login(Customers $customers){
        $params = $this->params;
        $result = $this->validate($params,'app\mall\validate\LoginUser');
        if (true !== $result) {
            // 验证失败 输出错误信息
            return err_return($result);
        }
        $user = $customers->field('password,headimgurl,shop_name,phone,province,city,area,role,is_agent,is_supplier,token,integral,detail_address,default_rec_address,salt,invite_img')->where(['phone' => $params['phone']])->find();
        if(empty($user)){
            return err_return(lang('no_user_name'));
        }
        if($user['password']==md5($params['password'].$user['salt'])){
//            登录成功
            $user['dizhi'] = $user->dizhi;
            unset($user['password']);
            unset($user['salt']);
            return right_return($user);
        }else{
            return err_return(lang('pwd_err'));
        }
    }

    /*获取用户信息*/
    public function get_user_info(){
        $user = $this->user;
        if (empty($user)){
            return err_return(lang('login_info_err'));
        }else{
            $user = $user->hidden(['password','salt'])->append(['dizhi']);
            return right_return($user);
        }
    }

    /*用户修改个人信息
        type: 修改内容
    */
    public function edit_user_info(ShortMessage $message){
        $user = $this->user;
        $params = $this->params;
        switch ($params['type']){
            case 'some':
                if (isset($params['headimgurl'])){
                    //删除原图片
                    if(empty($user->getData('headimgurl'))){
                        unlink(ROOT_PATH.'/public/'.$user->getData('headimgurl'));
                    }
                    $headimg = $this->handle_image($params['headimgurl'],ROOT_PATH.'/public/images/user/');
                    $user->headimgurl = $headimg;
                }
                if (isset($params['shop_name'])){
                    $user->shop_name = $params['shop_name'];
                }
                if (isset($params['cus_name'])){
                    $user->cus_name = $params['cus_name'];
                }
                if (isset($params['detail_address'])){
                    $user->detail_address = $params['detail_address'];
                }
                break;

            case 'password':
                if (md5($params['old_pwd'].$user['salt'])!=$user['password']){
                    return err_return(lang('err_old_pwd'));
                }
                //验证码相关
                $shortmsg = $message->getByPhone($params['phone']);
                if ($shortmsg['code']!=$params['code']){
                    return err_return(lang('code_err'));
                }
                if ($shortmsg['expire_time'] < time()){
                    return err_return(lang('expired_time'));
                }
                $salt = rand(1000,9999);
                $user->salt = $salt;
                $user->password = md5($params['new_pwd'].$salt);
                break;
        }
        $user->update_time = date('Y-m-d H:i:s');
        if($user->save()){
            return right_return('',lang('user_info_edit_success'));
        }else{
            return err_return(lang('user_info_edit_err'));
        }
    }


    /*收货地址*/
    public function my_receive_address(){
        $user = $this->user;
        $addrs = $user->rec_addrs;
        if (!$addrs->isEmpty()){
            $addrs1 = $addrs->append(['dizhi'])->toArray();
            uasort($addrs1,function($a,$b){
                return ($a['id'] < $b['id']) ? -1 : 1;
            });
            return right_return($addrs1);
        }else{
            return err_return(lang('no_receiving_address'));
        }
    }

    /*用户默认收货地址
     * $user
    */
    public function user_default_recaddr(MallReceivingAddress $mallReceivingAddress){
        $user = $this->user;
        $addrs = $user->rec_addrs;
        if (!$addrs->isEmpty()){
            $addrs1 = $addrs->append(['dizhi'])->hidden(['province','city','area','detail_address'])->toArray();
            $addr2 = array_filter($addrs1,function ($value){
                return $value['is_default'] == 1;
            });
            if (empty($addr2)){
                $chang = count($addrs1);
                return right_return($addr2[$chang-1]);
            }else{
                $addr2 = array_values($addr2);
                return right_return($addr2[0]);
            }
        }else{
            return err_return(lang('no_receiving_address'));
        }
    }

    /*添加收货地址
    province,city,area:省，市，区
    detail_address: 详细地址
    longitude: 经度
    latitude: 纬度
    receiver_name: 收货人姓名
    receiver_phone: 收货人电话
    */
    public function add_receive_address(MallReceivingAddress $address){
        $params = $this->params;
        $result = $this->validate($params,'app\mall\validate\AddReceiveAddr');
        if (true !== $result) {
            // 验证失败 输出错误信息
            return err_return($result);
        }
        $user = $this->user;
        $params['customer_id'] = $user['id'];
        $params['is_default'] = $params['is_default']=='false' ? false : true;

        if ($address->save($params)){
            //如果添加默认，修改原来的默认,并修改用户表的默认地址
            if ($params['is_default']){
                $address->save(['is_default' =>false],['id' => $user->default_rec_address]);
                $user->default_rec_address = $address->id;
                $user->save();
            }
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }

    }

    /* 删除收货地址
     * */
    public function del_receive_address(MallReceivingAddress $mallReceivingAddress){
        $user = $this->user;
        $params = $this->params;
        $address = $mallReceivingAddress->get($params['id']);
        if (empty($address)){
            return err_return(lang('err_addr'));
        }
        if ($user['id']!=$address['customer_id']){
            return err_return(lang('err_addr'));
        }
        if ($address->delete()){
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }

    }

    /* 修改收货地址信息
     * */
    public function edit_receive_address(MallReceivingAddress $mallReceivingAddress){
        $user = $this->user;
        $params = $this->params;
        $address = $mallReceivingAddress->get($params['addr_id']);
        if (empty($address)){
            return err_return(lang('err_addr'));
        }
        if ($user['id']!=$address['customer_id']){
            return err_return(lang('err_addr'));
        }
        $address->receiver_name = $params['receiver_name'];
        $address->receiver_phone = $params['receiver_phone'];
        $address->is_default = $params['is_default']=='false' ? false: true;
        $address->province = $params['province'];
        $address->city = $params['city'];
        $address->area = $params['area'];
        $address->detail_address = $params['detail_address'];

        if ($address->save()){
            //如果设置成默认地址
            if ($params['is_default']=='true'){
                //修改其他为非默认
                $mallReceivingAddress->where(['customer_id' => $user['id']])
                    ->whereNotIn('id',$user['default_rec_address'])
                    ->update(['is_default' => false]);
                //修改用户表中的默认地址
                $user->default_rec_address = $params['addr_id'];
                $user->save();
            }
            return right_return([],lang('edit_ok'));
        }else{
            return err_return(lang('edit_err'));
        }
    }

    public function get_phone_code(SendSms $sendSms){
        $params = $this->params;
        $phone = $params['phone'];
        $res = $sendSms->sms($phone);
        if ($res['code']==200){
            return right_return([],$res['msg']);
        }else{
            return err_return($res['msg']);
        }
    }

    /* 获取省市区
     * 根据省市区名，获取标号
     * */
    public function to_region(Region $region){
        $params = $this->params;
        $province = $params['province'];
        $city = $params['city'];
        $area = $params['area'];
        $province = $region->getFieldByName($province,'id');
        $city = $region->getFieldByName($city,'id');
        $area = $region->getFieldByName($area,'id');
        if (!empty($province)&&!empty($city)&&!empty($area)){
            return right_return([[$province,$params['province']],[$city,$params['city']],[$area,$params['area']]]);
        }else{
            return err_return(lang('err_diyu'));
        }
    }

    /* 积分变动流水
     *
     * */
    public function integral_log(MallUserIntegralLogs $userIntegralLogs){
        $user = $this->user;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $logs = $userIntegralLogs->where(['customer_id' => $user['id']])
            ->page($page,10)
            ->order(['create_time' => 'desc'])
            ->all();

        if (!$logs->isEmpty()){
            $sums = $userIntegralLogs->where(['customer_id' => $user['id']])
                ->field('sum(integral) as zong,type')
                ->all();
            $sums_type = [];
            foreach ($sums as $vl){
                $sums_type['k'.$vl['type']] = $vl['zong'];
            }
            unset($vl);
            return right_return(['logs' => $logs,'sums' => $sums_type]);
        }else{
            return err_return(lang('empty_logs'));
        }
    }

    /* 订单评价
     *
     * */
    public function my_evalute(MallOrderEvaluates $mallOrderEvaluates){
        $user = $this->user;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = 3;
        $my_evaluates = $mallOrderEvaluates->where(['customer_id' => $user['id']])
            ->field('order_id,customer_id,to_good,to_supplier,to_delivery,remark,create_time')
            ->order(['create_time' => 'desc'])
            ->page($page,$limit)
            ->all();

        if (!$my_evaluates->isEmpty()){
            //订单相关的商品信息
            foreach ($my_evaluates as $key => $value){
                $suffix = $value['order_id']%10==0  ? 10 : $value['order_id']%10;
                $table = 'mall_order_goods_'.$suffix;
                $goods = Db::table($table)->where(['order_id' => $value['order_id']])
                    ->field('good_price,good_nums,good_specs,good_name,good_img')
                    ->select();
                $my_evaluates[$key]['goods'] = $goods;
            }
            return right_return($my_evaluates);
        }else{
            return err_return(lang('empty_logs'));
        }

    }

    /* 我的配送
     * 配送员特有
     * */
    public function my_delivery(MallDeliveryLogs $mallDeliveryLogs){
        $user = $this->user;
        $params = $this->params;
        if ($user['role']!=4){
            return err_return(lang('not_delivery'));
        }
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = 3;
        $deliveries = $mallDeliveryLogs->where(['delivery_id' => $user['id'],'status' => 1])
            ->field('order_id,province,city,area,detail_address,customer_name,customer_tel,is_urgent')
            ->order(['create_time' => 'desc'])
            ->page($page,$limit)
            ->all();
        if (!$deliveries->isEmpty()){
            $deliveries = $deliveries->append(['dizhi'])->hidden(['province,city,area,detail_address'])->toArray();

            foreach($deliveries as $key =>$value){
                $suffix = $value['order_id']%10==0 ? 10 : $value['order_id']%10;
                $table = 'mall_order_goods_'.$suffix;
                $goods = Db::table($table)->where(['order_id' => $value['order_id']])
                    ->field('good_price,good_nums,good_specs,good_name,good_img')
                    ->select();
                $deliveries[$key]['goods'] = $goods;
            }
            return right_return($deliveries);
        }else{
            return err_return(lang('action_err'));
        }
    }

    /* 储存用户设备推送唯一标识符
     * */
    public function save_clientid(){
        $params = $this->params;
        $user = $this->user;
        $user->client_id = $params['client_id'];
        if ($user->save()){
            return right_return([],lang('action_ok'));
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