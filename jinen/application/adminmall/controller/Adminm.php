<?php


namespace app\adminmall\controller;


use think\Controller;
use think\facade\Session;
use think\facade\Request;
use think\facade\Cookie;
use app\common\model\MallSuppliers;
use app\common\model\Customers;
class Adminm extends Controller
{

    private $no_login = ['adminmall/login','adminmall/loginout','adminmall/edit_self_pwd','adminmall/edit_profile'];
    public function initialize()
    {
        parent::initialize();
        if (!$this->not_need_login()){
            if (!$this->is_login()){
                if (!$this->autologin()) {
                    $this->redirect('/adminmall/login');
                    return false;
                }
            }
            $sup_user = MallSuppliers::get(Session::get('mall_sup'));
            $this->assign('sup_user',$sup_user);
        }
    }

    protected function is_login(){
        $mall_user = Session::get('mall_user');
        if (empty($mall_user)){
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

    //记住密码
    public function keeplogin($cid){
        $keeptime = 86400;
        $expiretime = time() + $keeptime;
        $key = md5(md5($cid).md5($keeptime).md5($expiretime));
        $data = [$cid, $keeptime, $expiretime, $key];
        Cookie::set('keepslogin', implode('|', $data), 86400 * 30);
        return true;
    }

    //记住密码自动登录
    private function autologin(){
        $keeplogin = Cookie::get('keepslogin');
        if (!$keeplogin) {
            return false;
        }
        list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
        if ($id && $keeptime && $expiretime && $key && $expiretime > time()) {
            $admin = Customers::get($id);
            if (!$admin) {
                return false;
            }
            //token有变更
            if ($key != md5(md5($id).md5($keeptime).md5($expiretime))) {
                return false;
            }
            $ids = MallSuppliers::get(['customer_id'=>$admin['id']]);
            Session::set('mall_user',$admin['id']);
            Session::set('mall_sup',$ids['id']);
            //刷新自动登录的时效
            $this->keeplogin($admin['id']);
            return true;
        } else {
            return false;
        }
    }
}