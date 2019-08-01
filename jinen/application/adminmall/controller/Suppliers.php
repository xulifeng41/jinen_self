<?php


namespace app\adminmall\controller;
use app\common\model\MallSuppliers;
use app\common\model\Customers;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cookie;
use app\common\model\Region;
class Suppliers extends Adminm
{
    public function login(){
        if(Request::isPost()){
            if(Request::isAjax()){
                $params = Request::param();
                $validate = MallSuppliers::login_validate($params);
                if ($validate['status']==200) {
                    Session::set('mall_user',$validate['data']['id']);
                    Session::set('is_supplier',$validate['data']['is_supplier']);
                    Session::set('is_agent',$validate['data']['is_agent']);
                    Session::set('mall_sup',$validate['data']['sid']);
                    $params['remember'] = isset($params['remember']) ? $params['remember'] : 1;
                    if ($params['remember']==1) {
                        $this->keeplogin($validate['data']['id']);
                    }
                    return back(200,lang('login_right'));
                }else{
                    return back($validate['status'],$validate['msg']);
                }
            }else{
                $this->redirect('/adminmall');
            }
        }else{
            $this->assign('title',lang('login_title'));
            return $this->fetch();
        }
    }

    public function login_out()
    {
        Session::delete('mall_user');
        Session::delete('mall_sup');
        Cookie::delete("keepslogin");
        $this->redirect('/adminmall');
    }

    public function edit_profile(Region $region){
        $cid = Session::get('mall_user');
        $sid = Session::get('mall_sup');
        if (empty($cid)){
            $this->error(lang('login_first'),'/adminmall/login');
        }
        if (Request::isAjax()){
            $params = Request::param();
            unset($params['id']);

            if(MallSuppliers::update($params,['id' => $sid])){
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail')];
            }
        }else{
            $suppliers = MallSuppliers::get($sid);
            $provinces = $region->province();
            $this->assign('provinces',$provinces);
            $this->assign('suppliers',$suppliers);
            $cities = $region->where(['parent_id' => $suppliers['province']])->field('id,name')->all();
            $this->assign('cities',$cities);
            $areas = $region->where(['parent_id' => $suppliers['city']])->field('id,name')->all();
            $this->assign('areas',$areas);
            return $this->fetch();
        }
    }

    public function edit_pwd(){
        if (Request::isAjax()){
            $id = Session::get('mall_user');
            $params = array();
            $old_password = Request::param('old_password');
            $cus = Customers::get($id);
            if(md5(md5($old_password).$cus['salt'])!=$cus['password']){
                return ['code' => 200,'msg' => lang('old_pwd_err')];
            }
            $params['password'] = Request::param('password');
            $rand = rand(1000,9999);
            $params['salt'] = $rand;
            $params['password'] = md5(md5($params['password']).$rand);
            if(Customers::update($params,['id' => $id])){
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail'), 'is_self' =>false];
            }
        }else{
            return $this->fetch();
        }
    }

    //记住密码
    public function keeplogin($admin_id){
        $keeptime = 86400;
        $expiretime = time() + $keeptime;
        $key = md5(md5($admin_id).md5($keeptime).md5($expiretime));
        $data = [$admin_id, $keeptime, $expiretime, $key];
        Cookie::set('keepslogin', implode('|', $data), 86400 * 30);
        return true;
    }
}