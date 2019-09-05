<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/3/30
 * Time: 13:53
 */

namespace app\wechat\controller;

use app\common\model\Company;
use app\common\model\DevicePowers;
use app\wechat\model\Person;
use think\Db;
use think\Request;
use think\facade\Session;
use aliyun\SendSms;
use \GatewayWorker\Lib\Gateway;
ini_set("error_reporting","E_ALL & ~E_NOTICE");
class Personal extends Base
{
    public function index(Request $request,Company $company)
    {
        if($result=parent::_initialize())
        {
            # 存在session时根据表中数据
            $user_id = Session::get('user_id');
            $role=Person::where('id', $user_id)->find();
            $this->assign(
                [
                    'role' => $role
                ]);
            return $this->fetch();
        }else
        {
            Session::clear();
            # 不存在session时访问微信api
            if (empty(Session::get('appid'))){
                $company_id = $request->param('company_id');
                $company_id = empty($company_id) ? 1 : $company_id;
                $company_info=Db::table("company")->where('id',$company_id)->find();
                Session::set('appid',$company_info['appid']);
                Session::set('appsecret',$company_info['appsecret']);
                Session::set('company_id',$company_id);
            }
            $weixin = new \weixin\Wxapi(Session::get('appid'),Session::get('appsecret'),$company_id);
            # 第一次获取code
            if (!isset($_GET["code"])){
                $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $jumpurl = $weixin->oauth2_authorize($redirect_url, "snsapi_userinfo", "123");
                Header("Location: $jumpurl");
            }else{
                # 后续根据access_token获得相关信息
                $access_token_oauth2 = $weixin->oauth2_access_token($_GET["code"]);
                $userinfo = $weixin->oauth2_get_user_info($access_token_oauth2['access_token'], $access_token_oauth2['openid']);
                # 根据openid查询用户数据
                $role=Person::where('openid', $access_token_oauth2['openid'])->find();
                if($role)
                {
                    Session::delete('appid');
                    Session::delete('appsecret');
                    Session::delete('company_id');
                    # 库中有信息
                    Session::set('user_id',$role['id']);
                    $this->assign(
                        [
                            'role' => $role
                        ]);
                    return $this->fetch();
                }else
                {
                    # 库中无信息,进行注册操作
                    $this->assign(
                        [
                            'role'  => $userinfo,
                        ]);
                    return $this->fetch('bind');
                }
            }
        }
    }

    # 个人中心详情
    public function mydevice(Request $request,Company $company)
    {
        if($result=parent::_initialize())
        {
            $datas = $request->param();
            $user_id=isset($datas['uid'])?$datas['uid']:Session::get('user_id');
            $info=Person::where('id',$user_id)->find();
            if($info['role']==1)
            {
                $means=Db::table("devices")->where('customer_id',$user_id)->find();
                $info['uid']=$user_id;
            }elseif($info['role']==2)
            {
                $means=Db::table("customers")->where('salesman',$user_id)->find();
            }elseif($info['role']==3)
            {
                $means=Db::table("devices")->where('installer',$user_id)->find();
            }elseif($info['role']==4)
            {
                $area=$info['area'];
                $means=Db::table("devices")->where('area',$area)->where('env_show',1)->find();
            }elseif($info['role']==5)
            {
                $area=$info['area'];
                $means=Db::table("devices")->where('area',$area)->find();
            }
            $this->assign([
                'role' => $info,
                'means' => $means
            ]);
            return $this->fetch('add');
        }else
        {
            Session::clear();
            # 不存在session时访问微信api
            if (empty(Session::get('appid'))){
                $company_id = $request->param('company_id');
                $company_id = empty($company_id) ? 1 : $company_id;
                $company_info=Db::table("company")->where('id',$company_id)->find();
                Session::set('appid',$company_info['appid']);
                Session::set('appsecret',$company_info['appsecret']);
                Session::set('company_id',$company_id);
            }
            $weixin = new \weixin\Wxapi(Session::get('appid'),Session::get('appsecret'),$company_id);
            # 第一次获取code
            if (!isset($_GET["code"])){
                $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $jumpurl = $weixin->oauth2_authorize($redirect_url, "snsapi_userinfo", "123");
                Header("Location: $jumpurl");
            }else{
                # 后续根据access_token获得相关信息
                $access_token_oauth2 = $weixin->oauth2_access_token($_GET["code"]);
                $userinfo = $weixin->oauth2_get_user_info($access_token_oauth2['access_token'], $access_token_oauth2['openid']);
                # 根据openid查询用户数据
                $role=Person::where('openid', $access_token_oauth2['openid'])->find();
                if($role)
                {
                    Session::delete('appid');
                    Session::delete('appsecret');
                    Session::delete('company_id');
                    # 库中有信息
                    Session::set('user_id',$role['id']);
                    if($role['role']==1)
                    {
                        $means=Db::table("devices")->where('customer_id',$role['id'])->find();
                        $role['uid']=$role['id'];
                    }elseif($role['role']==2)
                    {
                        $means=Db::table("customers")->where('salesman',$role['id'])->find();
                    }elseif($role['role']==3)
                    {
                        $means=Db::table("devices")->where('installer',$role['id'])->find();
                    }elseif($role['role']==4)
                    {
                        $area=$role['area'];
                        $means=Db::table("devices")->where('area',$area)->where('env_show',1)->find();
                    }elseif($role['role']==5)
                    {
                        $area=$role['area'];
                        $means=Db::table("devices")->where('area',$area)->find();
                    }
                    $this->assign([
                        'role' => $role,
                        'means' => $means
                    ]);
                    return $this->fetch('add');
                }else
                {
                    # 库中无信息,进行注册操作
                    $this->assign(
                        [
                            'role'  => $userinfo
                        ]);
                    return $this->fetch('bind');
                }
            }
        }
    }

    # 业务员我售出的设备/安装员安装的设备/客户我的设备ajax数据
    public function usersajax(Request $request)
    {
        $datas = $request->param();
        $user_id=isset($datas['uid'])?$datas['uid']:Session::get('user_id');
        if($request->isAjax()) {
            switch($datas['role'])
            {
                case 1:
                    $means=Db::table("devices")->where('customer_id',$user_id)->count();
                    # 每页显示的数据条数
                    $rev='5';
                    # 获取最大页
                    $max=ceil($means/$rev);
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        $page=1;
                    }else
                    {
                        # 获取page参数
                        $page=$datas['page'];
                        # 判断
                        if(empty($page)){
                            $page=2;
                        }
                    }
                    $result=Db::view('devices', 'install_time,installer,id,version,sensor_type')
                        ->view('device_versions', 'version_name','device_versions.id=devices.version','LEFT')
                        ->view(['region' => 'a'], ['name' => 'aname'], 'devices.province=a.id','LEFT')
                        ->view(['region' => 'b'], ['name' => 'bname'], 'devices.city=b.id','LEFT')
                        ->view(['region' => 'c'], ['name' => 'cname'], 'devices.area=c.id','LEFT')
                        ->where('devices.customer_id', '=', $user_id)
                        ->order('devices.install_time', 'desc')
                        ->page($page,$rev)
                        ->select();
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        $results['maxpage']=1;
                    }else
                    {
                        $results['maxpage']=$max;

                    }
                    $results['idd']=$user_id;
                    $results[1]=$result;
                    echo json_encode($results);
                    break;
                case 2:
                    $means=Db::table("customers")->where('salesman',$user_id)->count();
                    # 每页显示的数据条数
                    $rev='3';
                    # 获取最大页
                    $max=ceil(($means-$rev)/$rev);
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        $page=1;
                    }else
                    {
                        # 获取page参数
                        $page=$datas['page'];
                        # 判断
                        if(empty($page)){
                            $page=2;
                        }
                    }
                    $result=Db::view('customers', ['id','cus_name','phone','detail_address' => 'address'])
                        ->view(['region' => 'a'], ['name' => 'aname'], 'customers.province=a.id','LEFT')
                        ->view(['region' => 'b'], ['name' => 'bname'], 'customers.city=b.id','LEFT')
                        ->view(['region' => 'c'], ['name' => 'cname'], 'customers.area=c.id','LEFT')
                        ->where('customers.salesman', '=', $user_id)
                        ->page($page,$rev)
                        ->select();

                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        echo json_encode($result);
                    }else
                    {
                        $results['maxpage']=$max;
                        $results[1]=$result;
                        echo json_encode($results);
                    }
                    break;
                case 3:
                    $where[] = ['devices.installer','=',$user_id];
                    if($datas['shop_name'])
                    {
                        $where[] = ['customers.shop_name','like','%'.$datas['shop_name'].'%'];
                    }else
                    {
                        $where[] = [1,'=',1];
                        $means=Db::table("devices")->where('installer',$user_id)->group('customer_id')->count();
                    }
                    # 每页显示的数据条数
                    $rev='15';
                    # 获取最大页
                    $max=ceil(($means-$rev)/$rev);

                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        $page=1;
                    }else
                    {
                        # 获取page参数
                        $page=$datas['page'];
                        # 判断
                        if(empty($page)){
                            $page=2;
                        }
                    }
                    if($datas['shop_name'])
                    {

                        $result=Db::view('customers', ['id','detail_address' => 'address','shop_name'])
                            ->view('devices', ['id' => 'did'], 'customers.id=devices.customer_id','LEFT')
                            ->view(['region' => 'a'], ['name' => 'aname'], 'customers.province=a.id','LEFT')
                            ->view(['region' => 'b'], ['name' => 'bname'], 'customers.city=b.id','LEFT')
                            ->view(['region' => 'c'], ['name' => 'cname'], 'customers.area=c.id','LEFT')
                            ->where([
                                $where
                            ])
                            ->group('devices.customer_id')
                            ->select();
                    }else
                        {

                            $result=Db::view('customers', ['id','detail_address' => 'address','shop_name'])
                                ->view('devices', ['id' => 'did'], 'customers.id=devices.customer_id','LEFT')
                                ->view(['region' => 'a'], ['name' => 'aname'], 'customers.province=a.id','LEFT')
                                ->view(['region' => 'b'], ['name' => 'bname'], 'customers.city=b.id','LEFT')
                                ->view(['region' => 'c'], ['name' => 'cname'], 'customers.area=c.id','LEFT')
                                ->where([
                                    $where
                                ])
                                ->group('devices.customer_id')
                                ->page($page,$rev)
                                ->select();
                        }
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        echo json_encode($result);
                    }else
                    {
                        $results['maxpage']=$max;
                        $results[1]=$result;
                        echo json_encode($results);
                    }
                    break;
                case 4:
                    $area=Person::area($user_id);
                    $means=Db::table("devices")->where('area',$area)->where('env_show',1)->count();
                    # 每页显示的数据条数
                    $rev='3';
                    # 获取最大页
                    $max=ceil(($means-$rev)/$rev);

                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        $page=1;
                    }else
                        {
                            # 获取page参数
                            $page=$datas['page'];
                            # 判断
                            if(empty($page)){
                                $page=2;
                            }
                        }
                    $result=Db::view('devices', 'install_time,installer,id,version,status,sensor_type,is_mine')
                        ->view('customers', 'shop_name,detail_address','devices.customer_id=customers.id')
                        ->view('device_versions', 'version_name','device_versions.id=devices.version','LEFT')
                        ->view(['region' => 'a'], ['name' => 'aname'], 'devices.province=a.id','LEFT')
                        ->view(['region' => 'b'], ['name' => 'bname'], 'devices.city=b.id','LEFT')
                        ->view(['region' => 'c'], ['name' => 'cname'], 'devices.area=c.id','LEFT')
                        ->where('devices.area', '=', $area)
                        ->where('devices.env_show', '=', 1)
                        ->order('devices.install_time', 'desc')
                        ->page($page,$rev)
                        ->select();
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        echo json_encode($result);
                    }else
                        {
                            $results['maxpage']=$max;
                            $results[1]=$result;
                            echo json_encode($results);
                        }
                    break;
                case 5:
                    $area=Person::area($user_id);
                    $means=Db::table("devices")->where('area',$area)->count();
                    # 每页显示的数据条数
                    $rev='3';
                    # 获取最大页
                    $max=ceil(($means-$rev)/$rev);
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        echo $page=1;
                    }else
                    {
                        # 获取page参数
                        $page=$datas['page'];
                        # 判断
                        if(empty($page)){
                            $page=2;
                        }
                    }
                    $result=Db::view('devices', 'install_time,installer,id,version,status,sensor_type,is_mine')
                        ->view('customers', 'shop_name,detail_address','devices.customer_id=customers.id')
                        ->view('device_versions', 'version_name','device_versions.id=devices.version','LEFT')
                        ->view(['region' => 'a'], ['name' => 'aname'], 'devices.province=a.id','LEFT')
                        ->view(['region' => 'b'], ['name' => 'bname'], 'devices.city=b.id','LEFT')
                        ->view(['region' => 'c'], ['name' => 'cname'], 'devices.area=c.id','LEFT')
                        ->where('devices.area', '=', $area)
                        ->order('devices.install_time', 'desc')
                        ->page($page,$rev)
                        ->select();
                    if(isset($datas['type'])&&$datas['type']==1)
                    {
                        echo json_encode($result);
                    }else
                    {
                        $results['maxpage']=$max;
                        $results[1]=$result;
                        echo json_encode($results);
                    }
                    break;
            }
        }
    }
    #
    public function inster(Request $request)
    {
        $user_id=$request->param('id');
        $this->assign('id',$user_id);
        return $this->fetch();
    }
    #
    public function inster_ajax(Request $request)
    {
        if($request->isAjax())
        {
            $datas = $request->param();
            $user_id=$datas['id'];
            $means=Db::table("devices")->where('customer_id',$user_id)->count();
            # 每页显示的数据条数
            $rev='3';
            # 获取最大页
            $max=ceil(($means-$rev)/$rev);
            if(isset($datas['type'])&&$datas['type']==1)
            {
                $page=1;
            }else
            {
                # 获取page参数
                $page=$datas['page'];
                # 判断
                if(empty($page)){
                    $page=2;
                }
            }
            $result=Db::view('devices', 'id,install_time,installer,version,device_code,is_mine,sensor_type')
                ->view('customers', ['cus_name', 'detail_address' => 'address'],'devices.customer_id=customers.id','LEFT')
                ->view('device_versions', 'version_name','device_versions.id=devices.version','LEFT')
                ->view(['region' => 'a'], ['name' => 'aname'], 'devices.province=a.id','LEFT')
                ->view(['region' => 'b'], ['name' => 'bname'], 'devices.city=b.id','LEFT')
                ->view(['region' => 'c'], ['name' => 'cname'], 'devices.area=c.id','LEFT')
                ->where('customers.id', '=', $user_id)
                ->order('devices.install_time', 'desc')
                ->page($page,$rev)
                ->select();

            if(isset($datas['type'])&&$datas['type']==1)
            {
                echo json_encode($result);
            }else
            {
                $results['maxpage']=$max;
                $results[1]=$result;
                echo json_encode($results);
            }
        }
    }
    #
    public function sell_device(Request $request)
    {
        $user_id=Session::get('user_id');
        $this->assign('id',$user_id);
        return $this->fetch();
    }
    #
    public function sell_dajax(Request $request)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $area=Person::area($datas['id']);
            $means=Db::table("devices")->where('area',$area)->where('env_show',1)->count();
            # 每页显示的数据条数
            $rev='3';
                    # 获取最大页
            $max=ceil(($means-$rev)/$rev);
            if(isset($datas['type'])&&$datas['type']==1)
            {
                $page=1;
            }else
            {
                # 获取page参数
                $page=$datas['page'];
                # 判断
                if(empty($page)){
                    $page=2;
                }
            }
            $result=Db::view('devices', 'install_time,installer,id,version,status,sensor_type,is_mine')
                ->view('customers', 'shop_name,detail_address','devices.customer_id=customers.id')
                ->view('device_versions', 'version_name','device_versions.id=devices.version','LEFT')
                ->view(['region' => 'a'], ['name' => 'aname'], 'devices.province=a.id','LEFT')
                ->view(['region' => 'b'], ['name' => 'bname'], 'devices.city=b.id','LEFT')
                ->view(['region' => 'c'], ['name' => 'cname'], 'devices.area=c.id','LEFT')
                ->where('devices.area', '=', $area)
                ->where('devices.env_show', '=', 1)
                ->order('devices.install_time', 'desc')
                ->page($page,$rev)
                ->select();

            if(isset($datas['type'])&&$datas['type']==1)
            {
                echo json_encode($result);
            }else
            {
                $results['maxpage']=$max;
                $results[1]=$result;
                echo json_encode($results);
            }
        }
    }
    #
    public function add()
    {
        $user_id = Session::get('user_id');
        $info=Person::where('id',$user_id)->find();
        switch($info['role'])
        {
            case 3:
                $version=Db::table('device_versions')
                    ->field(['id','version_name'])
                    ->where('is_delete','=','2')
                    ->select();
                # 下拉框数据
                foreach ($version as $k=>$v)
                {
                    $data[$k]['title']=$v['version_name'];
                    $data[$k]['value']=$v['id'];
                }
                $result=json_encode($data);
                $this->assign([
                    'info' => $info,
                    'version'=>$result,
                ]);
                break;
            default:
                #
                $cusinfos=Db::view('customers')
                    ->view(['region' => 'a'], ['name' => 'aname'], 'customers.province=a.id','LEFT')
                    ->view(['region' => 'b'], ['name' => 'bname'], 'customers.city=b.id','LEFT')
                    ->view(['region' => 'c'], ['name' => 'cname'], 'customers.area=c.id','LEFT')
                    ->where('customers.id', '=', $user_id)
                    ->select();
                $infos['address']=$cusinfos[0]['aname'].$cusinfos[0]['bname'].$cusinfos[0]['cname'];
                $this->assign([
                    'info' => $info,
                    'infos' => $infos,
                ]);
                break;
        }
        return $this->fetch('customers');
    }
    # 业务员选择用户
    public function selectuser(Request $request)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $user_id = Session::get('user_id');
            $where[] = ['a.id','=',$user_id];
            $where[] = ['b.role','=',1];
            if($datas['phone'])
            {
                $where[] = ['b.phone','=',$datas['phone']];
            }else
            {
                $where[] = [1,'=',1];
            }
            $cusinfos=Db::view(['customers' => 'a'],'role')
                ->view(['customers' => 'b'],('id,cus_name'), 'a.area=b.area')
                ->where([
                    $where
                ])
                ->where('b.salesman', '=', null)
                ->count();

            # 每页显示的数据条数
            $rev=$datas['phone']?'1':'15';
            # 获取最大页
            $max=ceil(($cusinfos-$rev)/$rev);
            if(isset($datas['type'])&&$datas['type']==1)
            {
                $page=1;
            }else
            {
                # 获取page参数
                $page=$datas['phone']?'1':$datas['page'];
                # 判断
                if(empty($page)){
                    $page=2;
                }
            }
            $result=Db::view(['customers' => 'a'],'role')
                ->view(['customers' => 'b'],('id,cus_name'), 'a.area=b.area')
                ->where([
                    $where
                ])
                ->where('b.salesman', '=', null)
                ->page($page,$rev)
                ->select();

            if(isset($datas['type'])&&$datas['type']==1)
            {
                echo json_encode($result);
            }else
            {
                $results['maxpage']=$max;
                $results[1]=$result;
                echo json_encode($results);
            }
        }
    }
    #
    public function sell_cus()
    {
        $user_id=Session::get('user_id');
        $this->assign('id',$user_id);
        return $this->fetch();
    }
    # 业务员选择页面
    public function sellbind(Request $request)
    {
        $datas=$request->param();
        $info=Person::where('id',$datas['id'])->find();
        $cusinfos=Db::view('customers','id')
            ->view(['region' => 'a'], ['name' => 'aname'], 'customers.province=a.id','LEFT')
            ->view(['region' => 'b'], ['name' => 'bname'], 'customers.city=b.id','LEFT')
            ->view(['region' => 'c'], ['name' => 'cname'], 'customers.area=c.id','LEFT')
            ->where('customers.id', '=', $datas['id'])
            ->select();
        $infos['address']=$cusinfos[0]['aname'].$cusinfos[0]['bname'].$cusinfos[0]['cname'];
        $this->assign([
            'info' => $info,
            'infos' => $infos
        ]);
        return $this->fetch('sellbind');
    }

    # 安装员判断设备码
    public function dcodeajax(Request $request)
    {
        if ($request->isAjax()) {
            $code=$request->param('code');
            $means=Db::table("devices")->where('device_code',$code)->find();
            if($means)
            {
                $result['code']='201';
                $result['msg']='该设备码已存在';
            }else
            {
                $result['code']='200';
                $result['msg']='该设备码可使用';
            }
            echo json_encode($result);
        }
    }
    # 安装员添加设备/业务员更新客户信息
    public function insert(Request $request)
    {
        if ($request->isAjax()) {
            $user_id = Session::get('user_id');
            $info=Person::where('id',$user_id)->find();
            $datas = $request->param('values');
            switch($info['role'])
            {
                # 业务员
                case 2:
                    $data['detail_address']=$datas['detail_address'];
                    $data['salesman']=$user_id;
                    $data['shop_name']=$datas['shop_name'];
                    $data['role']=1;
                    $data['update_time']=date("Y-m-d H:i:s",time());
                    $result=Db::name('customers')
                        ->where('id', $datas['id'])
                        ->update($data);
                    if($result)
                    {
                        $res['code']='200';
                        $res['msg']='选择完成';
                    }else
                    {
                        $res['code']='201';
                        $res['msg']='选择失败,请再次尝试';
                    }
                    echo json_encode($res);
                    break;
                # 安装员
                case 3:
                    $infos=Person::where('id',$datas['userid'])->find();
                    $data['customer_id']=$infos['id'];
                    $data['province']=$infos['province'];
                    $data['city']=$infos['city'];
                    $data['area']=$infos['area'];
                    $data['version']=$datas['versionid'];
                    $data['device_code']=$datas['device_code'];
                    $data['install_time']=date("Y-m-d H:i:s",time());
                    $data['installer']=$user_id;
                    $result=Db::name('devices')->insert($data);
                    if($result)
                    {
                        $res['code']='200';
                        $res['msg']='安装完成';
                    }else
                    {
                        $res['code']='201';
                        $res['msg']='安装失败,请再次尝试';
                    }
                    echo json_encode($res);
                    break;
            }
        }
    }
    # ajax选择地址后选择客户
    public function address(Request $request)
    {
        if($request->isAjax())
        {
            $datas = $request->param();
            $area=end($datas['citys']);
            $means=Db::table('customers')
                ->field(['id','cus_name'])
                ->where('area','=',$area)
                ->where('role','=','1')
                ->select();
            return $means;
        }
    }
    # ajax验证手机
    public function pregphone(Request $request)
    {
        if($request->isAjax())
        {
            $datas = $request->param();
            $info=Person::where('phone',$datas['phone'])->find();
            if($info)
            {
                $result['code']='201';
                $result['msg']='该手机号已存在';
                return $result;
            }else
            {
                $result['code']='200';
                $result['msg']='该手机号可使用';
                return $result;
            }
        }
    }
    # ajax触发短信接口
    public function sms(Request $request)
    {
        if($request->isAjax())
        {
            $phone = $request->param('phone');
            $send = new SendSms();
            return $send->sms($phone);
        }
    }
    # ajax验证短信验证码
    public function pregsms(Request $request)
    {
        if($request->isAjax())
        {
            $phone = $request->param('phone');
            $code = $request->param('code');
            $means=Db::table('short_message')
                ->field(['phone','code','expire_time'])
                ->where('phone','=',$phone)
                ->find();
            if($means)
            {
                if($means['code']==$code)
                {
                    if(time()>$means['expire_time'])
                    {
                        $result['code']='201';
                        $result['msg']='验证码已过期';
                        return $result;
                    }else{
                        $datas = $request->param();
                        $city=explode(',',$datas['city']);
                        if($city[0]=='')
                        {
                            # 城市默认值
                            $data['province']='330000';
                            $data['city']='330100';
                            $data['area']='330109';
                        }else
                        {
                            $data['province']=$city[0];
                            $data['city']=$city[1];
                            $data['area']=$city[2];
                        }
                        $data['role']='1';
                        $data['create_time']=date("Y-m-d H:i:s",time());
                        $data['cus_name']=$datas['cus_name'];
                        $data['headimgurl']=$datas['headimgurl'];
                        $data['nickname']=$datas['nickname'];
                        $data['openid']=$datas['openid'];
                        $data['phone']=$datas['phone'];
                        $data['token']=Db::raw("REPLACE(UUID(),'-','')");
                        $rand = rand(1000,9999);
                        $data['salt'] = $rand;
                        $data['password']=md5(md5(123456).$rand);
                        $data['wechat_id']=Session::get('company_id');
                        $result = Db::name('customers')->insertGetId($data);
                        if($result)
                        {
                            Session::set('user_id',$result);
                            return ['code' =>200, 'msg' => '绑定成功，正在跳转！'];
                        }
                    }
                }else
                {
                    $result['code']='202';
                    $result['msg']='验证码错误';
                    return $result;
                }
            }else
            {
                $result['code']='203';
                $result['msg']='上述手机号未进行验证';
                return $result;
            }
        }
    }


    # 客户修改信息/业务员修改客户信息/安装员修改设备型号
    public function edit(Request $request)
    {
        $role = $request->param('role');
        switch ($role)
        {
            # 客户
            case 1:
                $userid = $request->param('id');
                $info=Db::table('customers')->where('id',$userid)->find();
                $this->assign(
                    [
                        'info' => $info,
                    ]);
                return $this->fetch('edit');
                break;
            # 业务员
            case 2:
                $userid = $request->param('id');
                $info=Db::table('customers')->where('id',$userid)->find();
                $info['role']='2';
                $this->assign(
                    [
                        'info' => $info,
                    ]);
                return $this->fetch('edit');
                break;
            # 安装员
            case 3:
                $deviceid = $request->param('id');
                $info=Db::view('devices', 'id,device_code,version')
                    ->view(['device_versions'=> 'a'], 'version_name', 'devices.version=a.id')
                    ->where('devices.id', '=', $deviceid)
                    ->select();
                $infos['id']=$deviceid;
                $infos['version_name']=$info[0]['version_name'];
                $infos['device_code']=$info[0]['device_code'];
                $infos['version']=$info[0]['version'];
                $infos['role']='3';
                $version=Db::table('device_versions')
                    ->field(['id','version_name'])
                    ->where('is_delete','=','2')
                    ->select();
                # 型号下拉框数据
                foreach ($version as $k=>$v)
                {
                    $data[$k]['title']=$v['version_name'];
                    $data[$k]['value']=$v['id'];
                }
                $result=json_encode($data);
                $this->assign(
                    [
                        'info' => $infos,
                        'version'=>$result
                    ]);
                return $this->fetch('edit');
                break;
        }

    }
    # 执行客户修改信息/业务员修改客户信息/安装员修改设备型号
    public function update(Request $request)
    {
        if($request->isAjax()) {
            $datas = $request->param('values');
            switch ($datas['role'])
            {
//            # 客户
                case 1:
                    $userid=$datas['userid'];
                    $data['detail_address']=$datas['detail_address'];
                    $data['shop_name']=$datas['shop_name'];
                    $data['update_time']=date("Y-m-d H:i:s",time());
                    $result=Db::name('customers')
                        ->where('id', $userid)
                        ->update($data);
                    if($result)
                    {
                        $res['code']='200';
                        $res['msg']='修改完成';
                    }else
                    {
                        $res['code']='201';
                        $res['msg']='修改失败,请再次尝试';
                    }
                    echo json_encode($res);
                    break;
                case 2:
                    $userid=$datas['userid'];
                    $data['detail_address']=$datas['detail_address'];
                    $data['shop_name']=$datas['shop_name'];
                    $data['update_time']=date("Y-m-d H:i:s",time());
                    $result=Db::name('customers')
                        ->where('id', $userid)
                        ->update($data);
                    if($result)
                    {
                        $res['code']='200';
                        $res['msg']='修改完成';
                    }else
                    {
                        $res['code']='201';
                        $res['msg']='修改失败,请再次尝试';
                    }
                    echo json_encode($res);
                    break;
                case 3:
                    $id=$datas['deviceid'];
                    $data['version']=$datas['versionid'];
                    $result=Db::name('devices')
                        ->where('id', $id)
                        ->update($data);
                    # 没有修改
                    $res['code']='200';
                    $res['msg']='修改完成';
                    echo json_encode($res);
                    break;
            }
        }
    }


    # 客户设备监测页面
    public function jiance(Request $request)
    {
        $datas=$request->param();
        $num=intval($datas['deviceid'])%100;
        $deviceid=$datas['deviceid'];
        $this->assign(
            [
                'num' => $num,
                'deviceid' => $deviceid
            ]);
        return $this->fetch('device');
    }

    /* 设备的工作状况
     * */
    public function working_condition(Request $request,DevicePowers $devicePowers){
        $device_id = $request->param('id');
        if ($request->isAjax()){
            if ($request->isGet()){
                //请求数据库，这个设备的所有电源状态
                $powers = $devicePowers->where(['device_id' => $device_id])->all();
                if ($powers->isEmpty()){
                    return ['code' => 201];
                }else{
                    foreach($powers as $key=>$val){
                        $powers[$key]['high_voltage']=intval($val->high_voltage)*1000;
                    }
                    return ['code' => 200, 'data' => $powers];
                }
            }else{
                //先判断是否在线
                Gateway::$registerAddress = '127.0.0.1:1238';
                if (Gateway::isUidOnline($device_id)){
                //调整电源电压
                $type = $request->param('type');
                $power_num = $request->param('power_num');
                if ($request->param('switch_type')=='c_opower'){
                    //关闭开启电源
                    if ($type=='on'){
                        //开启电源
                        $str1 = substr_replace('01050000FF00',$power_num,1,1);
                    }else{
                        $str1 = substr_replace('010500000000',$power_num,1,1);
                    }
                    $str2 = $this->crc16($str1);
                    //发送命令
                    $str2 = $str1.strtoupper($str2);
                }elseif($request->param('switch_type')=='i_dpower')
                {
                    //调整电压前要看看电源是否是手动状态
                    $power = $devicePowers->where(['device_id' => $device_id, 'power_num' => $power_num])->find();
                    if ($power['is_manual']!=1){
                        return ['code' => 201, 'msg' => '电源必须在手动模式下才能调整'];
                    }
                    //调整电源电压
                    if ($type=='dec'){
                        //01050002FF00调整电源电压命令，第二位是电源号
                        $str1 = substr_replace('01050002FF00',$power_num,1,1);
                    }else{
                        $str1 = substr_replace('01050001FF00',$power_num,1,1);
                    }
                    $str2 = $this->crc16($str1);
                    //发送命令
                    $str2 = $str1.strtoupper($str2);
                } else{
                    //手自动调整
                    if ($type==1){
                        //手动模式
                        $str1 = substr_replace('01050003FF00',$power_num,1,1);
                    }else{
                        //自动模式
                        $str1 = substr_replace('010500030000',$power_num,1,1);
                    }
                }
                    Gateway::sendToUid($device_id,hex2bin(preg_replace('# #','', $str2)));
                return ['code' => 200, 'msg' => '操作命令已发出！'];
                }else{
                    return ['code' => 201, 'msg' => '当前设备不在线，无法调整'];
                }
            }
        }else{
            //发送指令，单片机可以循环给服务器发送电源信息
            //Gateway::$registerAddress = '127.0.0.1:1238';
            //Gateway::sendToUid($device_id,hex2bin(preg_replace('# #','', '01 05 00 70 FF 00 8D E1')));
            $powers = $devicePowers->where(['device_id' => $device_id])->all();
            foreach($powers as $key=>$val){
                $powers[$key]['high_voltage']=intval($val->high_voltage)*1000;
            }
            $user=Person::get(Session::get('user_id'));
            $this->assign('id',$device_id);
            $this->assign('powers',$powers);
            $this->assign('user',$user);
            return $this->fetch();
        }
    }

    # 图表页面根据设备id判断数据
    public function jianceajax(Request $request)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $data['deviceid']=$datas['deviceid'];
            $type=$datas['type'];
            $data['num']=$datas['num'];
            if($type==1)
            {
                $result=$this->turnvalue($data);
            }elseif($type==2)
            {
                $data['page']=$datas['page'];
                $result=$this->turnlist($data);
            }
            echo $result;
        }
    }

    # 由页面ajax传值之后返回图表值
    private function turnvalue($data)
    {
        $view="device_datas_".$data['num'];
        $means=Db::table("$view")->field(['soot','create_time'=>'ctime'])->where('device_id',$data['deviceid'])->whereTime('create_time', 'today')->select();
        $datas=[];
        if($means)
        {
            foreach ($means as $k=>$v)
            {
                if($v['soot']>10){
                    $soot=$v['soot']*0.2;
                }elseif($v['soot']>5)
                {
                    $soot=$v['soot']*0.3;
                }elseif($v['soot']>1)
                {
                    $soot=$v['soot']*0.5;
                }else
                {
                    $soot=$v['soot'];
                }
                $datas[1][$k][]=(strtotime($v['ctime'])+28800)*1000;
                $datas[1][$k][]=floor($soot*100) / 100;
                $avgs[]=floor($soot*100) / 100;
            }
            $datas['avg']=round(array_sum($avgs)/count($avgs),2);
        }else
        {
            $datas[1]=[];
        }
        return json_encode($datas);
    }
    private function turnlist($data)
    {
        $view="device_datas_".$data['num'];
        $means=Db::table("$view")->where('device_id',$data['deviceid'])->count();
        if($means)
        {
            # 每页显示的数据条数
            $rev='10';
            # 获取最大页
            $max=ceil($means/$rev);
            # 获取page参数
            $page=$data['page'];
            # 判断
            if(empty($page)){
                $page=1;
            }
            $result=Db::table("$view")->page($page,$rev)->field('soot,create_time')->where('device_id',$data['deviceid'])->order('create_time','desc')->select();
            foreach ($result as $k=>$v)
            {
                if($v['soot']>10){
                    $soot=$v['soot']*0.2;
                }elseif($v['soot']>5)
                {
                    $soot=$v['soot']*0.3;
                }elseif($v['soot']>1)
                {
                    $soot=$v['soot']*0.5;
                }else
                {
                    $soot=$v['soot'];
                }
                $datas[$k]['soot']=floor($soot*100) / 100;
                $datas[$k]['create_time']=$v['create_time'];
            }
            $results['maxpage']=$max;
            $results[1]=$datas;
        }else
        {
            $results[1]=[];
        }
        return json_encode($results);
    }
    # 报修
    public function repairs()
    {
        return $this->fetch('repairs');
    }
    # 报修页面ajax
    public function repairsajax(Request $request)
    {
        if($request->isAjax())
        {
            $data=$request->param('values');
            $data['reason']=$data['reason'];
            $data['phone']=$data['phone'];
            $data['costomer_id'] = Session::get('user_id');
            $info=Db::table("devices")->where('customer_id',$data['costomer_id'])->select();
            if($info)
            {
                $result=Db::name('repairs')->insert($data);
                if($result)
                {
                    $res['code']='200';
                    $res['msg']='上报成功';
                }else
                {
                    $res['code']='201';
                    $res['msg']='上报失败,请再次尝试';
                }
            }else
            {
                $res['code']='202';
                $res['msg']='您暂无绑定设备';
            }
            echo json_encode($res);
        }
    }
    # 我的报修
    public function myrepairs()
    {
        $user_id = Session::get('user_id');
        $info=Db::table('repairs')->where('costomer_id',$user_id)->select();
        $this->assign(
            [
                'info' => $info
            ]);
        return $this->fetch('myrepairs');
    }
    # 报修页面分页ajax
    public function repsajax(Request $request)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $user_id = Session::get('user_id');
            $info=Db::table('repairs')->field('id,reason,progress,create_time')->where('costomer_id',$user_id)->order('create_time','desc')->count();

            # 每页显示的数据条数
            $rev='5';
            # 获取最大页
            $max=ceil($info/$rev);
            if(isset($datas['type'])&&$datas['type']==1)
            {
                $page=1;
            }else
            {
                # 获取page参数
                $page=$datas['page'];
                # 判断
                if(empty($page)){
                    $page=2;
                }
            }
            $result=Db::table('repairs')->field('id,reason,progress,create_time')->where('costomer_id',$user_id)->order('create_time','desc')->page($page,$rev)->select();
            if(isset($datas['type'])&&$datas['type']==1)
            {
                echo json_encode($result);
            }else
            {
                $results['maxpage']=$max;
                $results[1]=$result;
                echo json_encode($results);
            }
        }
    }
    # 进度
    public function progress(Request $request)
    {
        $rid=$request->param('id');
        $info=Db::table('repairs')->field('progress')->where('id',$rid)->find();
        switch ($info['progress'])
        {
            case 1:
                $data['progress']='接受报修，指派工作人员';
                $data['color']='#000000';
                break;
            case 2:
                $data['progress']='维修人员已上门';
                $data['color']='#00BFFF';
                break;
            case 3:
                $data['progress']='问题已经解决';
                $data['color']='#90EE90';
                break;
            case 4:
                $data['progress']='问题还有待解决';
                $data['color']='#FF4500';
                break;
            default:
                $data['progress']='暂无处理进度,请耐心等待';
                $data['color']='#C0C0C0';
        }
        $this->assign(
            [
                'info' => $data
            ]);
        return $this->fetch('progress');
    }

    /*crc16校验码*/
    private function crc16($string){
        $string = pack('H*', $string);
        $crc = 0xFFFF;
        for ($x = 0; $x < strlen ($string); $x++) {
            $crc = $crc ^ ord($string[$x]);
            for ($y = 0; $y < 8; $y++) {
                if (($crc & 0x0001) == 0x0001) {
                    $crc = (($crc >> 1) ^ 0xA001);
                } else {
                    $crc = $crc >> 1;
                }
            }
        }
        return sprintf('%02x%02x', $crc%256, floor($crc/256));
    }

}