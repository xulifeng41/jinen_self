<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:23
 */

namespace app\admin\controller;
//use think\captcha\Captcha;
use app\admin\model\AuthGroup;
use app\admin\model\Region;
use rsa\Sign;
use think\facade\Request;
use app\admin\model\ManagerPc;
use think\facade\Session;
use think\facade\Cookie;

class Users extends Admin
{
    public function login(){
        if(Request::isPost()){
            if(Request::isAjax()){
                $params = Request::param();
//            if(!captcha_check($params['code'])){
//                return fanhui(201,lang('wrong_code'));
//            }
                $validate = ManagerPc::login_validate($params);
                if ($validate['status']==200) {
                    Session::set('admin_user',$validate['data']['id']);
                    $params['remember'] = isset($params['remember']) ? $params['remember'] : 1;
                    if ($params['remember']==1) {
                        $this->keeplogin($validate['data']['id']);
                    }
                    return fanhui(200,lang('login_right'));
                }else{
                    return fanhui($validate['status'],$validate['msg']);
                }
            }else{
                $this->redirect('/admin');
            }
        }else{
            $this->assign('title',lang('login_title'));
            return $this->fetch();
        }
    }

    public function login_by_token($token){
        if (empty($token)){
            return $this->error('token验证失败！');
        }
        $sign = new Sign();
        $de_token = $sign->rsaDecrypt($token);
        if (strpos($de_token,'&') !== false){
            $en_token1 = explode('&',$de_token);
            $params = array();
            foreach ($en_token1 as $k => $value){
                $vv = explode('=',$value);
                $params[$vv[0]] = $vv[1];
            }

            $validate = ManagerPc::login_validate($params);
            if ($validate['status']==200) {
                Session::set('admin_user',$validate['data']['id']);
                $params['remember'] = isset($params['remember']) ? $params['remember'] : 1;
                if ($params['remember']==1) {
                    $this->keeplogin($validate['data']['id']);
                }
                $this->redirect('/admin');
            }else{
                return $this->error($validate['msg']);
            }
        }else{
            return $this->error('token验证失败！');
        }
    }

//    public function verify(){
//        $config =    [
//            // 验证码字体大小
//            'fontSize'    =>    35,
//            // 验证码位数
//            'length'      =>    4,
//            // 关闭验证码杂点
//            'useNoise'    =>    false,
//        ];
//        $captcha = new Captcha($config);
//        return $captcha->entry();
//    }

    public function login_out()
    {
        Session::delete('admin_user');
        Cookie::delete("keeplogin");
        $this->redirect('/admin');
    }

    public function edit_profile(AuthGroup $authGroup,Region $region){
        $id = Session::get('admin_user');
        if (empty($id)){
            $this->error(lang('login_first'),'/admin/login');
        }
        if (Request::isAjax()){
            $params = Request::param();
            unset($params['id']);
            //检查用户名是否重复
            $names = ManagerPc::where(['user_name' => $params['user_name']])
                ->whereNotIn('id',$id)->find();
            if(!empty($names)){
                return ['code' => 201, 'msg' => lang('user_name_repeat')];
            }
            if(ManagerPc::update($params,['id' => $id])){
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail')];
            }
        }else{
            $manager = ManagerPc::get($id);
            $provinces = $region->province();
            $this->assign('provinces',$provinces);
            $this->assign('manager',$manager);
            $cities = $region->where(['parent_id' => $manager['province']])->field('id,name')->all();
            $this->assign('cities',$cities);
            $areas = $region->where(['parent_id' => $manager['city']])->field('id,name')->all();
            $this->assign('areas',$areas);
            return $this->fetch();
        }
    }

    public function edit_pwd(){
        if (Request::isAjax()){
            $id = Session::get('admin_user');
            $params = array();
            $old_password = Request::param('old_password');
            $manager = ManagerPc::get($id);
            if(md5(md5($old_password).$manager['salt'])!=$manager['password']){
                return ['code' => 200,'msg' => lang('old_pwd_err')];
            }
            $params['password'] = Request::param('password');
            $rand = rand(1000,9999);
            $params['salt'] = $rand;
            $params['password'] = md5(md5($params['password']).$rand);
            if(ManagerPc::update($params,['id' => $id])){
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
        Cookie::set('keeplogin', implode('|', $data), 86400 * 30);
        return true;
    }
}