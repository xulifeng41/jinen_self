<?php


namespace app\admin\controller;


use app\admin\model\AuthGroup;
use app\admin\model\ManagerPc;
use app\admin\model\Menus;
use think\Request;


class Permission extends Admin
{
    public function index(Request $request,AuthGroup $auth_group){
        if ($request->isAjax()){
            $params = $request->param();
            $page = isset($params['page']) ? $params['page'] : 1;
            $limit = isset($params['limit']) ? $params['limit'] : 1;
            $groups = $auth_group->show_groups($this->now_user->auth);
            $zus = array();
            foreach ($groups as $k=>$val){
                $zus[$k]['id'] = $val[0];
                $zus[$k]['name'] = $val[1];
                $zus[$k]['parent_id'] = 2;
                $zus[$k]['is_prohibit'] = '允许登陆';
            }
//            $groups = $auth_group->where(['is_delete' => 2])->page($page,$limit)->all();
//            $zong = $auth_group->where(['is_delete' => 2])->count();
            return array('code'=> 0, 'msg' => '', 'count' => count($zus), 'data' => $zus);
        }else{
            return $this->fetch();
        }
    }

    public function add_group(Request $request,AuthGroup $auth_group,Menus $menus){
        if($request->isAjax()){
            if($request->isGet()){
                //权限随选择父级发生变化
                $parent_id = empty($request->param('parent_id')) ? '' : $request->param('parent_id');
                if(empty($parent_id)){
                    //输出登录用户的权限
                    $auths = $this->auths;
                    if (in_array('*',$auths)){
                        $where = ['is_delete' => 2];
                    }else{
                        $where = [['id','in',$auths]];
                    }
                    $menues = $menus->where($where)->field('id,title,parent_id')->all();
                    return ['code' => 200, 'data' => $menues];
                }else{
                    $auths = $auth_group->find($parent_id);
                    $auths = explode('-',$auths['auths']);
                    $where = [['id','in',$auths]];
                    $menues = $menus->where($where)->field('id,title,parent_id')->all();
                    $menues = array_map(function($value){
                        $xin = [];
                        $xin['id'] = $value['id'];
                        $xin['pId'] = $value['parent_id'];
                        $xin['name'] = $value['title'];
                        $xin['open'] = true;
                        return $xin;
                    },$menues->toArray());
                    return $menues;
                }


            }else{
                $params = $request->param();
                if (!isset($params['is_prohibit'])){
                    $params['is_prohibit'] = 'no';
                }
                $params['create_time'] = date('Y-m-d H:i:s');
                if($auth_group->save($params)){
                    return ['code' => 200, 'msg' => lang('add_ok')];
                }else{
                    return ['code' => 201, 'msg' => lang('add_fail')];
                }
            }

        }else{
            $groups = $auth_group->show_groups($this->now_user->auth);
            $this->assign('groups',$groups);
            return $this->fetch();
        }
    }

    public function edit_group(Request $request,AuthGroup $auth_group,Menus $menus,ManagerPc $managerPc){
        $id = $request->param('id');
        $group = $auth_group->get($id);
        if($request->isAjax()){
            if($request->isGet()){
                $parent_id = empty($request->param('parent_id')) ? '' : $request->param('parent_id');
                if (empty($parent_id)){
                    //输出当前的权限
                    $auths = explode('-',$group['auths']);
                    if (in_array('*',$auths)){
                        $where = ['is_delete' => 2];
                    }else{
                        $where = [['id','in',$auths]];
                    }
                    $menues = $menus->where($where)
                        ->field('id,title,parent_id')->all();
                    return ['code' => 200, 'data' => $menues];
                }else{
                    $selected_group = $auth_group->find($parent_id);
                    $auths = explode('-',$selected_group['auths']);
                    if (in_array('*',$auths)){
                        $where = ['is_delete' => 2];
                    }else{
                        $where = [['id','in',$auths]];
                    }
                    $menues = $menus->where($where)
                        ->field('id,title,parent_id')->all();
                    $menues = array_map(function($value){
                        $xin = [];
                        $xin['id'] = $value['id'];
                        $xin['pId'] = $value['parent_id'];
                        $xin['name'] = $value['title'];
                        $xin['checked'] = true;
                        $xin['open'] = true;
                        return $xin;
                    },$menues->toArray());
                    return $menues;
                }

            }else{
                $params = $request->param();
                if (!isset($params['is_prohibit'])){
                    $params['is_prohibit'] = 'no';
                    $managerPc->save(['is_delete' => 2],['auth_group' => $id]);
                }
                $params['update_time'] = date('Y-m-d H:i:s');
                $id = $params['id'];
                unset($params['id']);
                if($auth_group->save($params,['id'=>$id])){
                    return ['code' => 200, 'msg' => lang('edit_ok')];
                }else{
                    return ['code' => 201, 'msg' => lang('edit_fail')];
                }
            }

        }else{
            $groups = $auth_group->show_groups($this->now_user->auth);
            $this->assign('groups',$groups);
            $this->assign('group',$group);
            return $this->fetch();
        }
    }

    public function del(Request $request,AuthGroup $auth_group,ManagerPc $managerPc){
        $id = $request->param('id');
        if($auth_group->save(['is_delete'=>1],['id'=>$id])){
            $managerPc->save(['is_delete' => 2],['auth_group' => $id]);
            return ['code' => 200, 'msg' => lang('del_ok')];
        }else{
            return ['code' => 201, 'msg' => lang('del_fail')];
        }
    }

}