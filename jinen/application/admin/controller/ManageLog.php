<?php


namespace app\admin\controller;


use app\admin\model\ManagerLogs;
use think\Request;

class ManageLog extends Admin
{
    public function index(Request $request,ManagerLogs $managerLogs){
        if ($request->isAjax()){
            $page = empty($request->param('page')) ? 1 : $request->param('page');
            $limit = empty($request->param('limit')) ? 10 : $request->param('limit');
            $url = empty($request->param('url')) ? '' : $request->param('url');
            if(empty($url)){
                $where = [];
            }else{
                $where = ['url' => $url];
            }
            $logs = $managerLogs->where($where)->page($page,$limit)->all();
            $zong = $managerLogs->where($where)->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $logs);
        }else{
            return $this->fetch();
        }
    }
}