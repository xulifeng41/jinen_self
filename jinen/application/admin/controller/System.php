<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 10:24
 */

namespace app\admin\controller;


use app\admin\model\SystemConfig;
use think\Request;

class System extends Admin
{
    public function index(Request $request,SystemConfig $systemConfig)
    {
        if ($request->isAjax()){
            $type = $request->param('type');
            $datas = $request->param('datas');
            $content = $systemConfig->getByType($type);
            if(empty($content)){
                $systemConfig->save(['type'=>$type,'content'=>json_encode($datas),'create_time'=>date('Y-m-d H:i:s')]);
                $msg = '保存成功！';
            }else{
                $content->content = json_encode($datas);
                $content->update_time = date('Y-m-d H:i:s');
                $content->save();
                $msg = '修改成功！';
            }
            return ['code' => 200, 'msg' => $msg];
        }else{
            $settings = $systemConfig->getFieldByType('setting','content');
            $warnings = $systemConfig->getFieldByType('warning','content');
            $weixin = $systemConfig->getFieldByType('weixin','content');
            $this->assign('setting',json_decode($settings,true));
            $this->assign('warning',json_decode($warnings,true));
            $this->assign('weixin',json_decode($weixin,true));
            return $this->fetch();
        }

    }



}