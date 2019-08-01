<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 17:00
 */

namespace app\admin\controller;
use app\admin\model\Customers;
use app\admin\model\Devices;
use app\admin\model\DeviceVersions;
use think\Db;
use think\Request;
use app\admin\model\Region;


class Device extends Admin
{
    public function index(Request $request, Region $region){
        if ($request->isAjax()){
            !empty($request->param('province')) ? $province = $request->param('province') : $province = '' ;
            !empty($request->param('city')) ? $city = $request->param('city') : $city = '' ;
            !empty($request->param('area')) ? $area = $request->param('area') : $area = '' ;
            !empty($request->param('customer_id')) ? $customer_id = $request->param('customer_id') : $customer_id = '' ;
            $sql = '';
            if(ADROLE!=1) {
                $arr1 = [AREA,CITY,PROVINCE];
            }else{
                $arr1 = [];
            }
            $arr = ['customer_id','area','city','province'];
            for($i = 0;$i<count($arr);$i++){
                $a = $arr[$i];
                if($i<count($arr)-LEVEL){
                    if (!empty($$a)) {
                        if(empty($sql)){
                            $sql = 'd.'.$a.'='.$$a;
                        }else{
                            $sql .= ' and d.'.$a.'='.$$a;
                        }
                    }
                }else{
                    if(ADROLE!=1) {
                        if (empty($sql)) {
                            $sql = 'd.' . $a . '=' . $arr1[$i - 1];
                        } else {
                            $sql .= ' and d.' . $a . '=' . $arr1[$i - 1];
                        }
                    }
                }
                unset($a);
            }
            unset($i);

            $page = empty($request->param('page')) ? 1 : $request->param('page');
            $limit = empty($request->param('limit')) ? 15 : $request->param('limit');
            $status = $request->param('status');
            if (!empty($status)){
                if (empty($sql)){
                    $sql ='d.status='.$status;
                }else{
                    $sql .=' and d.status='.$status;
                }
            }
            if (ADROLE==3){
                if (empty($sql)){
                    $sql ='d.env_show=1';
                }else{
                    $sql .=' and d.env_show=1';
                }
            }
            $devices = Devices::where($sql)
                        ->alias('d')
                        ->leftJoin('customers c','c.id = d.customer_id')
                        ->leftJoin('device_versions dv','dv.id = d.version')
                        ->field('d.id,d.device_code,d.version,d.customer_id,c.cus_name,d.install_time,d.status,d.province,d.city,d.area,dv.version_name,d.total_time,d.env_show')
                        ->page($page,$limit)
                        ->order('id','desc')
                        ->select();
            $zong = Devices::where($sql)
                ->alias('d')
                ->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $devices);
        }else{
            if (ADROLE!=1){
                $provinces = $region->field('id,name')->where('id',PROVINCE)->all();
            }else{
                $provinces = $region->province();
            }
            $this->assign('provinces', $provinces);
            return $this->fetch();
        }

    }

    public function edit(Request $request, Region $region){
        if ($request->isAjax()){
            $datas = $request->param();
            Devices::update($datas);
            return ['code'=>200,'msg'=>lang('edit_ok')];
        }else{
            $device_id = $request->param('id');
            $device = Devices::get($device_id);
            if (empty($device)){
                $this->error('设备信息有误！请稍后再试');
            }

            $versions = DeviceVersions::all();
            $this->assign('versions',$versions);

            $provinces = $region->province();
            $this->assign('provinces',$provinces);

            $cities = $region->where('parent_id',$device['province'])->field('id,name')->all();
            $this->assign('cities',$cities);
            $areas = $region->where('parent_id',$device['city'])->field('id,name')->all();
            $this->assign('areas',$areas);
            $kehus = Customers::where(['province'=>$device['province'],'city'=>$device['city'],'area'=>$device['area']])->field('id,cus_name')->all();
            $this->assign('kehus',$kehus);
            $this->assign('device',$device);
            $this->assign('role',ADROLE);
            return $this->fetch();
        }
    }

    public function monitor_datas(Request $request,Db $db){
        if($request->isAjax()){
            if($request->isGet()){
                $id = $request->param('id');
                $postfix = $id%100;
                $datas = $db::table('device_datas_'.$postfix)
                            ->where('device_id',$id)
                            ->whereTime('create_time', 'today')
                            ->fieldRaw('soot,date_format(create_time,"%H:%i") as shijian')
                            ->select();
                return ['datas' => $datas];
            }else{

            }
        }else{
            $id = $request->param('id');
            $this->assign('id',$id);
            $device = Devices::with('costomer')->get($id);
            $this->assign('device',$device);
            $dizhi = Region::where('id','in',[$device->costomer['province'],$device->costomer['city'],$device->costomer['area']])->field('id,name')->order('id','asc')->select();
            $dizhi2 = array_column($dizhi->toArray(),'name');
            $this->assign('dizhi',$dizhi2);
            return $this->fetch();
        }
    }

    public function monitor_datas_table(Request $request,Db $db){
        $id = $request->param('id');
        $page = empty($request->param('page')) ? 1 : $request->param('page');
        $limit = empty($request->param('limit')) ? 5 : $request->param('limit');
        $shijian = empty($request->param('shijian')) ? '' : $request->param('shijian');
        if(empty($shijian)){
            $where = ['device_id'=>$id];
        }else{
            $time_range = explode('~',$shijian);
            $where = [['device_id','=',$id],['create_time','between',$time_range]];
        }

        $postfix = $id%100;
        $datas = $db::table('device_datas_'.$postfix)
            ->where($where)
            ->field('soot,pellet,not_methane,fan_status,voltage,wind_speed,fire_controll,leakage,create_time')
            ->page($page,$limit)
            ->order(['create_time'=>'desc'])
            ->select();
        $zong = $db::table('device_datas_'.$postfix)
            ->where($where)->count();
        return ['code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $datas];
    }

    public function close(Request $request){
        $id = $request->param('id');
        if(ADROLE==2){
            $data = ['id' => $id, 'switch1' => 2];
        }else{
            $data = ['id' => $id, 'switch2' => 2];
        }
        Devices::update($data);
        return ['code'=>200,'msg'=>'关停指令已发出'];
    }

    public function env_show(Request $request,Devices $devices){
        if ($request->isAjax()){
            $env = $request->param('env_show');
            $id = $request->param('id');
            if ($devices->save(['env_show' => $env],['id' => $id])){
                if (!empty($env)){
                    return ['code' =>200, 'msg' => lang('env_show')];
                }else{
                    return ['code' =>200, 'msg' => lang('env_close')];
                }
            }else{
                return ['code' =>201, 'msg' => lang('action_fail')];
            }
        }

    }

    public function region(Request $request, Region $region){
        $parent_id = $request->param('parent_id');
        if(ADROLE!=1){
            $id = '';
            if (substr($parent_id,2,4)==0000){
                //市级
                if(LEVEL==3){
                    $datas['id'] = CITY;
                }elseif(LEVEL==2){
                    $datas['id'] = CITY;
                }else{
                    $datas['parent_id'] = $parent_id;
                }
            }else{
                //县级
                if(LEVEL==3){
                    $datas['id'] = AREA;
                }else{
                    $datas['parent_id'] = $parent_id;
                }
            }
            return $region->field('id,name')->where($datas)->all();
        }else{
            return $region->field('id,name')->where('parent_id',$parent_id)->all();
        }

    }

}