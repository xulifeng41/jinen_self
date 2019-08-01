<?php


namespace app\mall\controller;


use app\common\model\Customers;
use app\common\model\MallVersions;
use rsa\Sign;
use think\Controller;

class Notices extends Controller
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

    /* 系统消息
     *
     * */
    public function s_notices(){
        $user = $this->user;

    }

    /* 删除系统消息
     * */
    public function del_notices(){

    }

    /* 标记为已读
     * */
    public function remark_notice(){

    }

    /* APP更新
     * 返回当前最新的版本号
     * */
    public function update_version(MallVersions $mallVersions){
        $vinfo = $mallVersions->order(['create_time' => 'desc'])->limit(1)->find();
        if (empty($vinfo)){
            return err_return('');
        }else{
            return right_return($vinfo);
        }

    }


}