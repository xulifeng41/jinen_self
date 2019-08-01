<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/4/1
 * Time: 10:21
 */

namespace app\wechat\controller;
use think\facade\Session;
use think\Controller;

class Base extends Controller
{
    public function _initialize(){
        parent::initialize(); //
        if (empty(Session::has('user_id'))){
            return false;
        } else {
            return true;
        }
    }
    protected function is_login(){

    }
}