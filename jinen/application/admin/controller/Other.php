<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 14:03
 */

namespace app\admin\controller;


use app\admin\model\AuthGroup;
use app\admin\model\Customers;
use app\admin\model\Devices;
use app\admin\model\DeviceVersions;
use app\admin\model\Region;
use think\Db;
use think\Request;
use weixin\WeChat;

class Other extends Admin
{
    public function index(Region $region,DeviceVersions $deviceVersions,Customers $cus){
        $province = $region->province();
        $this->assign('provinces',$province);
        $vers = $deviceVersions->field('id,version_name')->all();
        $this->assign('versions',$vers);
        $installers = $cus->where(['role' => 3])->field('id,cus_name')->all();
        $this->assign('installers',$installers);
        return $this->fetch();
    }

    public function add_custom(Request $request,Customers $cus){
        $params = $request->param();
        if($cus->save($params)){
            return ['code' => 200];
        }else{
            return ['code' => 201];
        }
    }

    public function add_device(Request $request,Devices $devices){
        $params = $request->param();
        $params['install_time'] = date('Y-m-d H:i:s');
        $chong = $devices->getByDeviceCode($params['device_code']);
        if(!empty($chong)){
            return ['code' =>201, 'msg' => lang('repeat_dcode')];
        }
        if ($devices->save($params)){
            return ['code' => 200, 'msg' => lang('add_ok')];
        }else{
            return ['code' => 201, 'msg' => lang('add_fail')];
        }
    }

    public function test_weixin(WeChat $weChat){
        //测试公司
        $corporation = $this->now_user->company;
        dump($corporation);
//        return $weChat->send_msg('','测试短信来一个');
    }


}