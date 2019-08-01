<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/25
 * Time: 10:14
 */

namespace app\admin\controller;


use app\admin\model\Customers;
use app\admin\model\Region;
use think\Request;

class Customer extends Admin
{
    public function index(Request $request,Customers $cus,Region $region){
        if($request->isAjax()){
            $cans = $request->param();
            empty($cans['role']) ? $cans['role'] = 1 : '';
            $page = empty($cans['page']) ? 1 : $cans['page'];
            $limit = empty($cans['limit']) ? 10 : $cans['limit'];
            $cans1 = array_filter($cans);
            unset($cans1['limit']);
            unset($cans1['page']);
            $where = $cans1;
            if(ADROLE!=1){
                $arr = ['area','city','province'];
                $arr1 = [AREA,CITY,PROVINCE];
                for($i=0;$i<count($arr);$i++){
                    if($i<count($arr)-LEVEL){
                        if(isset($cans1[$arr[$i]])&&!empty($cans1[$arr[$i]])){
                            $where[$arr[$i]] = $cans1[$arr[$i]];
                        }
                    }else{
                        $where[$arr[$i]] = $arr1[$i];
                    }
                }
            }
            unset($i);
            $where1 = array();
            foreach($where as $k1=>$val)
            {
                if($k1=='cus_name'||$k1=='shop_name'){
                    $where1[] = [$k1,'like','%'.$val.'%'];
                }else{
                    $where1[] = [$k1,'=',$val];
                }
            }
            unset($k);
            unset($val);
            $zong = $cus->where($where1)->count();
            $cuses = $cus->where($where1)->page($page,$limit)->order('id','desc')->all();
            foreach($cuses as $k2=>$v){
                $cuses[$k2]['di_zhi'] = $v->dizhi;
            }
            unset($v);
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $cuses);
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

    public function edit(Request $request,Customers $cus,Region $region){
        if ($request->isAjax()){
            $datas = $request->param();
            $id = $datas['id'];
            unset($datas['id']);
            if($cus->save($datas,['id'=>$id])){
                return ['code'=>200,'msg'=>lang('edit_ok')];
            }else{
                return ['code'=>201,'msg'=>lang('edit_fail')];
            }
        }else{
            $id = $request->param('id');
            $cuser = $cus::get($id);
            $this->assign('cuser',$cuser);
            $provinces = $region->province();
            $this->assign('provinces',$provinces);

            $cities = $region->where('parent_id',$cuser['province'])->field('id,name')->all();
            $this->assign('cities',$cities);
            $areas = $region->where('parent_id',$cuser['city'])->field('id,name')->all();
            $this->assign('areas',$areas);
            return $this->fetch();
        }
    }

    public function insert(Request $request,Customers $cus)
    {
        $params = $request->param();
        if($cus->save($params)){
            return ['code' => 200, 'msg' => lang('add_ok')];
        }else{
            return ['code' => 201, 'msg' => lang('add_fail')];
        }
    }

    public function sellers(Request $request){
        $province = $request->param('province');
        $city = $request->param('city');
        $area = $request->param('area');
        $datas = ['province' => $province, 'city' => $city, 'area' => $area];
        $sellers = Customers::where($datas)->field('id,cus_name')->all();
        return ['code' => 200, 'data' => $sellers];
    }

}