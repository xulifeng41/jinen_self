<?php
namespace app\admin\controller;

use app\admin\model\AuthGroup;
use app\admin\model\ManagerPc;
use app\admin\model\Region;
use think\Request;

class Manager extends Admin
{
    public function index(Request $request,ManagerPc $managers){
        if ($request->isAjax()){
            $params = $request->param();
            $where = array();
            if (ADROLE!=1){
                $where = ['province' => PROVINCE, 'city' => CITY, 'area' =>AREA];
                if (ADROLE==3){
                    $where['role'] = 3;
                }
                $where = array_filter($where);
            }
            $page = isset($params['page']) ? $params['page'] : 1;
            $limit = isset($params['limit']) ? $params['limit'] : 1;
            $guanlis = $managers->where($where)->page($page,$limit)->all();
            $zong = $managers->where($where)->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $guanlis);
        }else{
            return $this->fetch();
        }
    }

    public function add_manager(Request $request,ManagerPc $managers,AuthGroup $authGroup,Region $region){
        if ($request->isAjax()){
            $params = $request->param();
            //用户名不能重复
            $names = $managers->where(['user_name' => $params['user_name']])->find();
            if(!empty($names)){
                return ['code' => 201, 'msg' => lang('user_name_repeat')];
            }
            if(!isset($params['is_delete'])){
                $params['is_delete'] = 'no';
            }
            $rand = rand(1000,9999);
            $params['salt'] = $rand;
            $params['password'] = md5(md5($params['password']).$rand);
            $params['create_time'] = date('Y-m-d H:i:s');
            $params['company_id'] = $this->now_user->company_id;
            if($managers->save($params)){
                return ['code' => 200, 'msg' => lang('add_ok')];
            }else{
                return ['code' => 201, 'msg' => lang('add_fail')];
            }
        }else{
            //用户的权限组只能是用户所在权限组和以后的权限组
            $groups = $authGroup->show_groups($this->now_user->auth);
            $this->assign('groups',$groups);
            if (ADROLE!=1){
                $provinces = $region->field('id,name')->where('id',PROVINCE)->all();
            }else{
                $provinces = $region->province();
            }
            $this->assign('provinces',$provinces);
            return $this->fetch();
        }
    }

    public function edit(Request $request,ManagerPc $managers,AuthGroup $authGroup,Region $region){
        $id = $request->param('id');
        if ($request->isAjax()){
            $params = $request->param();
            unset($params['id']);
            //检查用户名是否重复
            $names = $managers->where(['user_name' => $params['user_name']])
                                ->whereNotIn('id',$id)->find();
            if(!empty($names)){
                return ['code' => 201, 'msg' => lang('user_name_repeat')];
            }
            if($managers->save($params,['id' => $id])){
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail')];
            }
        }else{
            $groups = $authGroup->show_groups($this->now_user->auth);
            $this->assign('groups',$groups);
            if (ADROLE!=1){
                $provinces = $region->field('id,name')->where('id',PROVINCE)->all();
            }else{
                $provinces = $region->province();
            }
            $this->assign('provinces',$provinces);
            $manager = $managers->find($id);
            $this->assign('manager',$manager);
            $cities = $region->where(['parent_id' => $manager['province']])->field('id,name')->all();
            $this->assign('cities',$cities);
            $areas = $region->where(['parent_id' => $manager['city']])->field('id,name')->all();
            $this->assign('areas',$areas);
            return $this->fetch();
        }
    }

    public function edit_pwd(Request $request,ManagerPc $managerPc){
        $id = $request->param('id');
        if ($request->isAjax()){
            $params = array();

            $params['password'] = $request->param('password');
            $rand = rand(1000,9999);
            $params['salt'] = $rand;
            $params['password'] = md5(md5($params['password']).$rand);
            if($managerPc->save($params,['id' => $id])){
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail')];
            }
        }else{
            $this->assign('id',$id);
            return $this->fetch();
        }
    }

    public function del_manager(Request $request,ManagerPc $managerPc){
        $id = $request->param('id');
        if ($managerPc->save(['is_delete' => 2],['id' => $id])){
            return ['code' => 200,'msg' => lang('del_ok')];
        }else{
            return ['code' => 201,'msg' => lang('del_fail')];
        }
    }



}