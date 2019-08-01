<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 14:12
 */

namespace app\admin\controller;

use think\Request;
use app\admin\model\Menus;

class AdminMenu extends Admin
{
//    主菜单列表
    public function index(Request $request,Menus $menus){
        $parent_id = empty($request->param('parent_id')) ? 0 : $request->param('parent_id');
        if ($request->isAjax()){
            $page = empty($request->param('page')) ? 1 : $request->param('page');
            $limit = empty($request->param('limit')) ? 1 : $request->param('limit');
            $menus1 = $menus->where(['parent_id' => $parent_id,'is_delete' => 2])
                            ->page($page,$limit)
                            ->order(['sort'=>'asc'])
                            ->select();
            $zong = $menus->where(['parent_id' => $parent_id,'is_delete' => 2])->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $menus1);
        }else{
            $this->assign('parent_id',$parent_id);
            return $this->fetch();
        }
    }

    public function add_menu(Request $request,Menus $menus){
        if ($request->isAjax()){
            $params = $request->param();
            !isset($params['is_show']) ? $params['is_show'] = 'no' : '';
            !isset($params['is_menu']) ? $params['is_menu'] = 'no' : '';
            if(isset($params['id'])){
                $id = $params['id'];
                unset($params['id']);
                if($menus->save($params,['id' => $id])){
                    return ['code' => 200, 'msg' => lang('edit_ok')];
                }else{
                    return ['code' => 201, 'msg' => lang('edit_fail')];
                }
            }else{
                if($menus->save($params)){
                    return ['code' => 200, 'msg' => lang('add_ok')];
                }else{
                    return ['code' => 201, 'msg' => lang('add_fail')];
                }
            }
        }else{
            $parent_id = empty($request->param('parent_id')) ? 0 : $request->param('parent_id');
            $id = empty($request->param('id')) ? 0 : $request->param('id');
            $menu = $menus::get($id);
            $this->assign('parent_id',$parent_id);
            $this->assign('id',$id);
            $this->assign('menu',$menu);
            return $this->fetch();
        }
    }

    public function del(Request $request,Menus $menus){
        $id = $request->param('id');
        $data = ['is_delete' => 1];
        if($menus->save($data,['id' => $id])){
            return ['code' => 200, 'msg' => lang('del_ok')];
        }else{
            return ['code' => 201, 'msg' => lang('del_fail')];
        }
    }


}