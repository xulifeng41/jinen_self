<?php


namespace app\adminmall\controller;
use think\Request;
use think\facade\Session;
use app\common\model\MallGoods;
use app\common\model\MallCatagories;
use app\common\model\MallSuppliers;
use app\common\model\Customers;
class Goods extends Adminm
{
    public function index(MallGoods $goods,Request $request,MallCatagories $cates)
    {
        if($request->isAjax()){
            $limit = $request->param('limit');
            $page = $request->param('page');
            $list = $goods->where('is_delete','0')->where('supplier_id',Session::get('mall_sup'))->page($page,$limit)->order('create_time','desc')->all();
            $count = $goods->where('is_delete','0')->where('supplier_id',Session::get('mall_sup'))->count();
            return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list->toArray());
        }else{
            $cates = $cates->where('is_delete', 0)
                ->field('id,cat_name')->all();
            $this->assign('cates',$cates);
            $this->assign('title','商品管理');
            return $this->fetch();
        }
    }

    public function recycle(MallGoods $goods,Request $request)
    {
        if($request->isAjax()){
            $limit = $request->param('limit');
            $page = $request->param('page');
            $list = $goods->where('is_delete','1')->where('supplier_id',Session::get('mall_sup'))->page($page,$limit)->order('update_time','desc')->all();
            $count = $goods->where('is_delete','1')->where('supplier_id',Session::get('mall_sup'))->count();
            return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list->toArray());

        }else{
            $this->assign('title','商品管理');
            return $this->fetch();
        }
    }

    public function add_goods(Request $request,MallGoods $goods,MallCatagories $cates,MallSuppliers $suppliers)
    {
        if ($request->isAjax())
        {
            $supplier = $suppliers::get(Session::get('mall_sup'));
            $params = $request->except('image,images');
            $params['is_sale']=isset($params['is_sale'])?1:0;
            $params['create_time']=date('Y-m-d H:i:s');
            $params['supplier_id']=Session::get('mall_sup');
            $params['province']=$supplier->province;
            $params['city']=$supplier->city;
            $params['area']=$supplier->area;
            if($goods->save($params))
            {
                return ['code' => 200, 'msg' => lang('add_ok')];
            }else
            {
                return ['code' => 201, 'msg' => lang('add_fail')];
            }
        }else
        {
            $catess = $cates->where('is_delete', 0)
                ->field('id,cat_name')->all();
            $this->assign('catess',$catess);
            return $this->fetch();
        }
    }

    public function edit_goods(Request $request,MallGoods $goods,MallCatagories $cates)
    {
        $gid = $request->param('id');
        $good = $goods::get($gid);
        if ($request->isAjax())
        {
            //图片在数据库中的值
            $old_img = $goods->getFieldById($gid, 'good_img');
            if($request->param('is_change')==1)
            {
                $params = $request->except(['image','is_change','is_sale']);
            }else
            {
                $params = $request->except(['image','is_change','good_img','is_sale']);
            }
            $params['is_sale']=$request->param('is_sale')?1:0;
            $params['update_time']=date('Y-m-d H:i:s');
            $params['is_check']=0;
            if(MallGoods::update($params,['id' => $gid])){
                if($request->param('is_change')==1)
                {
                    $url=$_SERVER["DOCUMENT_ROOT"].'/'.$old_img;
                    unlink($url);
                }
                return ['code' => 200,'msg' => lang('edit_ok')];
            }else{
                return ['code' => 201,'msg' => lang('edit_fail')];
            }
        }else
        {
            $catess = $cates->where('is_delete', 0)
                ->field('id,cat_name')->all();
            $this->assign('catess',$catess);
            $this->assign('goodss',$good);
            return $this->fetch();
        }
    }

    public function show_imgs(Request $request,MallGoods $goods)
    {
        $gid = $request->param('id');
        $good = $goods::get($gid);
        $sid=Session::get('mall_sup');
        if ($request->isAjax())
        {
            $type=$request->param('type');
            if($type==1)
            {
                $show_imgs = $good->show_imgs;
                if($show_imgs)
                {
                    $imgs_list=explode('-',$show_imgs);
                    foreach ($imgs_list as $k=>$v)
                    {
                        $data['imgs_list'][]='/'.$v;;
                    }
                    $data['show_img']=$show_imgs;
                }else
                {
                    $data['imgs_list']=array();
                    $data['show_img']='';
                }
                return $data;
            }else
                {
                    $data['show_imgs'] = $request->param('show_imgs');
                    $data['update_time'] = date('Y-m-d H:i:s');
                    if(MallGoods::update($data,['id' => $gid])){
                        if($request->param('old_show_imgs'))
                        {
                            $old_arr=explode('-',$request->param('old_show_imgs'));
                            $new_arr=explode('-',$request->param('show_imgs'));
                            $diff=array_values(array_diff($old_arr,$new_arr));
                            foreach ($diff as $k=>$v)
                            {
                                $url=$_SERVER["DOCUMENT_ROOT"].'/'.$v;
                                unlink($url);
                            }
                        }
                        return ['code' => 200,'msg' => lang('edit_ok')];
                    }else{
                        return ['code' => 201,'msg' => lang('edit_fail')];
                    }
                }
        }else
        {
            $this->assign('good',$good);
            return $this->fetch();
        }
    }

    public function del(Request $request,MallGoods $goods){
        if ($request->isAjax()){
            $id = $request->param('id');
            $type = $request->param('type');
            $good = $goods::get($id);
            $sid=Session::get('mall_sup');
            if($type=='recycle')
            {
                $good->is_delete = 1;
                $good->update_time = date('Y-m-d H:i:s');
                $result = $good->save();
                if($result){
                    return ['code'=>200,'msg'=>lang('recycle_ok')];
                }else{
                    return ['code'=>201,'msg'=>lang('recycle_fail')];
                }
            }elseif($type=='del')
            {
                //图片在数据库中的值
                $old_img = $goods->getFieldById($id, 'good_img');
                $result = $good->delete();
                if($result){
                    if($good->show_imgs)
                    {
                        $show_imgs=explode('-',$good->show_imgs);
                        foreach ($show_imgs as $k=>$v)
                        {
                            $urls=$_SERVER["DOCUMENT_ROOT"]."/".$v;
                            unlink($urls);
                        }
                    }
                    $url=$_SERVER["DOCUMENT_ROOT"].'/'.$old_img;
                    unlink($url);
                    return ['code'=>200,'msg'=>lang('del_ok')];
                }else{
                    return ['code'=>201,'msg'=>lang('del_fail')];
                }
            }
        }
    }

    public function recover(Request $request,MallGoods $goods)
    {
        if ($request->isAjax()){
            $id = $request->param('id');
            $good = $goods::get($id);
            $good->is_delete = 0;
            $good->update_time = date('Y-m-d H:i:s');
            $result = $good->save();
            if($result){
                return ['code'=>200,'msg'=>lang('recover_ok')];
            }else{
                return ['code'=>201,'msg'=>lang('recover_fail')];
            }
        }
    }

    public function is_sale(Request $request,MallGoods $goods){
        if ($request->isAjax()){
            $is_sale = intval($request->param('is_sale'));
            $id = $request->param('id');
            if ($goods->save(['is_sale' => $is_sale,'update_time'=>date('Y-m-d H:i:s')],['id' => $id])){
                if ($is_sale){
                    return ['code' =>200, 'msg' => lang('sale')];
                }else{
                    return ['code' =>200, 'msg' => lang('not_sale')];
                }
            }else{
                return ['code' =>201, 'msg' => lang('action_fail')];
            }
        }
    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        // 移动到框架应用根目录/uploads/ 目录下
        $sid=Session::get('mall_sup');
        $info = $file->validate(['size'=>1024*1024*2,'ext'=>'jpg,png,gif'])->move( "./images/good");
        if($info){
            // 成功上传后 获取上传信息
//            echo $info->getSaveName();
            return  ["code"=> 0,"msg"=> "","data"=> [ "src"=>'images/good/'.$info->getSaveName()]];
        }else{
            // 上传失败获取错误信息
//            echo $file->getError();
            return ["code"=> 1,"msg"=> $info->getError(),"data"=> ''];
        }
    }

    public function uploads(){
        // 获取表单上传文件
        $files = request()->file('images');
        $sid=Session::get('mall_sup');
        foreach($files as $file){
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>1024*1024*2,'ext'=>'jpg,png,gif'])->move( "./images/good");
            if($info){
                return  ["code"=> 0,"msg"=> "","data"=> [ "src"=> 'images/good/'.$info->getSaveName()]];
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    public function is_check(MallGoods $goods,Customers $cus,Request $request)
    {
        $cuser=$cus->get(Session::get('mall_user'));
        $area=$cuser->area?$cuser->area:$cuser->city;
        if($request->isAjax()){
            if($request->param('type')==1)
            {
                $limit = $request->param('limit');
                $page = $request->param('page');
                $list = $goods->where('is_delete','0')->where('is_check','0')->where($cuser->area?'area':'city',$area)->page($page,$limit)->order('create_time','desc')->all();
                $count = $goods->where('is_delete','0')->where('is_check','0')->where($cuser->area?'area':'city',$area)->count();
                return array('code'=> 0, 'msg' => '', 'count' => $count, 'data' => $list->toArray());
            }elseif($request->param('type')==2)
            {
                if(MallGoods::update(['is_check'=>1,'update_time'=>date('Y-m-d H:i:s')],['id' => $request->param('id')])){
                    return ['code' => 200,'msg' => lang('edit_ok')];
                }else{
                    return ['code' => 201,'msg' => lang('edit_fail')];
                }
            }
        }else{
            $this->assign('title','商品审核');
            return $this->fetch();
        }
    }


}