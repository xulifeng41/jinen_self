<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14
 * Time: 17:00
 */

namespace app\admin\controller;
use app\admin\model\Menus;
use think\Controller;

use think\facade\Session;
use think\facade\Request;
use think\facade\Cookie;
use app\admin\model\ManagerPc;

class Admin extends Controller
{
    private $no_login = ['admin/login','admin/verify','admin/loginout','admin/edit_self_pwd','admin/edit_profile','admin/login_by_token'];
    protected $auths = '';
    private $path = '';
    protected $now_user = '';

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        //是否需要登录
        if (!$this->not_need_login()){
            if (!$this->is_login()){
                if (!$this->autologin()) {
                    $this->redirect('/admin/login');
                    return false;
                }
            }
            //权限管理
            $admin_id = Session::get('admin_user');
            $admin_user = ManagerPc::get($admin_id);
            $this->now_user = $admin_user;
            //权限验证
            if(!$this->auth($admin_user)){
//            验证失败
                if(Request::isAjax()){
                    exit(json_encode(['code'=> 201,'msg' =>lang('no_right')]));
                }else{
                    $this->error(lang('no_right'));
                }
            }
            //用户菜单
            $user_menus = $this->user_menus();
            //登录用户的身份
            !defined('ADROLE')&&define("ADROLE",$admin_user['role']) ? true : false;
            //登录用户级别
            !defined('LEVEL')&&define("LEVEL",$admin_user['level']) ? true : false;
            if ($admin_user['role']!=1){
                !defined('PROVINCE')&&define("PROVINCE",$admin_user['province']) ? true : false;
                !defined('CITY')&&define("CITY",$admin_user['city']) ? true : false;
                !defined('AREA')&&define("AREA",$admin_user['area']) ? true : false;
            }
            $this->assign('admin_user',$admin_user);
            $this->assign('menus',$user_menus);
        }
    }

    protected function is_login(){
        $admin_user = Session::get('admin_user');
        if (empty($admin_user)){
            return false;
        } else {
            return true;
        }
    }

    protected function not_need_login(){
        $current_url = Request::path();
        $this->path = $current_url;
        if (in_array($current_url,$this->no_login)){
            return true;
        }else{
            return false;
        }
    }

    //记住密码自动登录
    private function autologin(){
        $keeplogin = Cookie::get('keeplogin');
        if (!$keeplogin) {
            return false;
        }
        list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
        if ($id && $keeptime && $expiretime && $key && $expiretime > time()) {
            $admin = ManagerPc::get($id);
            if (!$admin) {
                return false;
            }
            //token有变更
            if ($key != md5(md5($id).md5($keeptime).md5($expiretime))) {
                return false;
            }
            Session::set('admin_user',$admin['id']);
            //刷新自动登录的时效
            $this->keeplogin($admin['id']);
            return true;
        } else {
            return false;
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

    //权限判断
    public function auth($admin_user){
        //Request::path(); 当前url的路由
        $path = $this->path;
        $menu_id = Menus::getFieldByUrl($path,'id');
        $auths = $admin_user->auth->auths;
        $auths = explode('-',$auths);
        $this->auths = $auths;
        if($path=='admin'){
            return true;
        }
        if(in_array('*',$auths)){
            return true;
        }
        if (in_array($menu_id,$auths)){
            return true;
        }else{
            return false;
        }
    }

    //菜单
    public function user_menus(){
        $field = 'id,title,url,action,parent_id';
        if(in_array('*',$this->auths)){
            $menuses = Menus::where(['is_show' => 1, 'is_delete' => 2])
                                ->field($field)
                                ->order(['sort' => 'asc'])
                                ->all();
        }else{
            $menuses = Menus::where(['is_show' => 1, 'is_delete' => 2])
                ->whereIn('id',$this->auths)
                ->field($field)
                ->order(['sort' => 'asc'])
                ->all();
        }

        $yiji = array_map(function($value){
            if ($value['parent_id']==0){
                return $value;
            }
        },$menuses->toArray());
        $yiji = array_filter($yiji);
        foreach($menuses as $val){
            if($this->path==$val['url']){
                $val['is_now'] = 1;
            }
            foreach($yiji as $kk=>$value){
                if($val['parent_id'] == $value['id']){
                    $yiji[$kk]['child_menu'][] = $val->toArray();
                    if(isset($val['is_now'])){
                        $yiji[$kk]['is_now'] = 1;
                    }
                }else{
                    $yiji[$kk]['child_menu'][] = [];
                }
            }
            unset($kk);
            unset($value);
        }
        $yiji = array_map(function($value){
            $value['child_menu'] =  array_filter($value['child_menu']);
            return $value;
        },$yiji);
        return $yiji;
    }

}