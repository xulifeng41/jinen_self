<?php


namespace app\admin\behavior;

use app\admin\model\ManagerLogs;
use app\admin\model\Menus;
use think\facade\Session;
use think\Request;

class Jinen
{
    public function appEnd(Request $request,$params){
        if($request->isAjax()&&$request->isPost()){
            $url = $request->path();
            $action_content = Menus::getFieldByUrl($url,'title');
            $ip = $_SERVER['REMOTE_ADDR'];
            $brower = $_SERVER['HTTP_USER_AGENT'];
            $logs = array();
            $logs['url'] = $url;
            $logs['action_content'] = $action_content;
            $logs['ip'] = $ip;
            $logs['brower'] = $brower;
            $logs['create_time'] = date('Y-m-d H:i:s');
            $logs['manager_id'] = Session::get('admin_user');
            ManagerLogs::create($logs);
        }
    }

}