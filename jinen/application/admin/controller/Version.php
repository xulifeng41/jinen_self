<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 17:49
 */

namespace app\admin\controller;


use app\admin\model\DeviceVersions;
use think\Request;

class Version extends Admin
{
    public function index(DeviceVersions $version,Request $request)
    {
        if($request->isAjax()){
            $limit = $request->param('limit');
            $page = $request->param('page');

            $vers = $version->where('is_delete','2')->page($page,$limit)->order('id','desc')->all();
            $zong = $version->where('is_delete','2')->count();
            return array('code'=> 0, 'msg' => '', 'count' => $zong, 'data' => $vers->toArray());

        }else{
            return $this->fetch();
        }
    }

    public function add_version(Request $request,DeviceVersions $version)
    {
        if ($request->isAjax()){
            $vname = $request->param('version_name');
            $instruction = $request->param('instruction');
            $image = $request->param('image');
            $content = $request->param('content');
            if($request->param('type')=='edit'){
                $id = $request->param('id');
                $jieguo = $version->save(['version_name'=>$vname,'instruction'=>$instruction,'image'=>$image,'content'=>$content,'create_time'=>date('Y-m-d H:i:s')],['id'=>$id]);
            }else{
                $jieguo = $version->save(['version_name'=>$vname,'instruction'=>$instruction,'image'=>$image,'content'=>$content,'create_time'=>date('Y-m-d H:i:s')]);
            }
            if($jieguo){
                return ['code'=>200];
            }else{
                return ['code'=>201];
            }
        }else{
            $type = $request->param('type');
            if(!empty($type)){
                $id = $request->param('id');
                $device = $version::get($id);
                $this->assign('device',$device);
            }
            return $this->fetch();
        }
    }

    public function del(Request $request,DeviceVersions $version){
        $id = $request->param('id');
        $ver = $version::get($id);
        $ver->is_delete = 1;
        $jieguo = $ver->save();
        if($jieguo){
            return ['code'=>200,'msg'=>lang('del_ok')];
        }else{
            return ['code'=>200,'msg'=>lang('del_fail')];
        }
    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->validate(['size'=>1024*1024*2,'ext'=>'jpg,png,gif'])->move( './static/images');
        if($info){
            // 成功上传后 获取上传信息
//            echo $info->getSaveName();
            return  ["code"=> 0,"msg"=> "","data"=> [ "src"=> $info->getSaveName()]];

        }else{
            // 上传失败获取错误信息
//            echo $file->getError();
            return ["code"=> 1,"msg"=> $info->getError(),"data"=> ''];
        }
    }

}