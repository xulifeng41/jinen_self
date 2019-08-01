<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use rsa\Sign;

class Index extends Controller
{
    public function index(){
        $this->redirect('/admin');
    }

    public function get_token(Request $request,Sign $sign){
        if ($request->isPost()){
            $params = $request->param();
            if (empty($params)){
                return json(['code' => 201, 'msg' => '参数丢失！']);
            }
            $params = array_filter($params);
            $p_keys = array_keys($params);
            if ($p_keys!=['admin_name','admin_password']){
                return json(['code' => 201, 'msg' => '参数错误！']);
            }
            $token = $sign->get_token($params);
            $token = urlencode($token);
            return json(['code' => 200, 'token' => $token]);
        }else{
        	return 'post go';
        }
          
    }

}
