<?php

namespace app\wechatmall\controller;

use app\wechatmall\model\MallUserAddress;
use app\wechatmall\model\MallWechatUser;
use app\wechatmall\model\MallCashOut;
use think\facade\Session;
use think\Request;
use app\common\model\Region;
class User extends Base
{
    public function index(MallWechatUser $wuser)
    {
        $info=$wuser->get(Session::get('wuser_id'));
        $info['time']=date("Y-m-d H:i:s");
        $num=$wuser->get_num(Session::get('wuser_id'));
        $this->assign('num',$num);
        $this->assign('info',$info);
        $this->assign('title','个人中心');
        return $this->fetch();
    }

    public function address_list(Request $request)
    {
        $datas=$request->param();
        $type=isset($datas['type'])?$datas['type']:'';
        $cart_ids=isset($datas['cart_ids'])?$datas['cart_ids']:'';
        $this->assign('type',$type);
        $this->assign('cart_ids',$cart_ids);
        $this->assign('title','管理收货地址');
        return $this->fetch();
    }

    public function list_ajax(MallWechatUser $wuser,Request $request)
    {
        if($request->isAjax())
        {
            //加载
            $datas=$request->param();
            $wuser_id=Session::get('wuser_id');
            $info=$wuser->get($wuser_id);
            $result=$info->user_address()->field('id,real_name,phone,province,city,area,detail_address,is_default')->order('is_default desc,create_time desc')->page($datas['page'],5)->select()->toarray();
            foreach ($result as $k=>$v)
            {
                $dizhi = Region::where('id','in',[$v['province'],$v['city'],$v['area']])->field('id,name')->order('id','asc')->select()->toArray();
                $result[$k]['region']=implode('',array_column($dizhi,'name'));
            }
            $count=$info->user_address()->count();
            $infos=parent::paging($count,$result,$datas['page']);
            return $infos;
        }

    }
    public function add_address(Request $request)
    {
        $datas=$request->param();
        $cart_ids=isset($datas['cart_ids'])?$datas['cart_ids']:'';
        $this->assign('cart_ids',$cart_ids);
        $this->assign('title','添加收货地址');
        return $this->fetch();
    }

    public function add_ajax(Request $request,MallUserAddress $address)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $params['wuser_id']=Session::get('wuser_id');
            $params['real_name']=$datas['real_name'];
            $params['phone']=$datas['phone'];
            $params['detail_address']=$datas['detail_address'];
            $params['is_default']=isset($datas['is_default'])?1:0;
            $city=explode(',',$datas['city']);
            $params['province']=$city[0];
            $params['city']=$city[1];
            $params['area']=$city[2];
            if($address->save($params))
            {
                $new_id=$address->id;
                //提交数据为默认地址时进行状态更改
                if($params['is_default'])
                {
                    $default=$address->where('wuser_id',Session::get('wuser_id'))->where('is_default',1)->field('id')->select()->toArray();
                    if(count($default)>1)
                    {
                        foreach ($default as $k=>$v)
                        {
                            if($v['id']!=$new_id)
                            {
                                $old_info=$address->get($v['id']);
                                $old_info->is_default=0;
                                $old_info->save();
                            }
                        }
                    }
                }
                $result['code']='200';
                $result['add_id']=$new_id;
                $result['msg']='添加成功';
            }else
                {
                    $result['code']='201';
                    $result['msg']='添加失败';
                }
            return $result;
        }
    }

    public function edit_address(Request $request,MallUserAddress $address)
    {
        $id=$request->param('id');
        $info=$address->get($id);
        $dizhi = Region::where('id','in',[$info['province'],$info['city'],$info['area']])->field('id,name')->order('id','asc')->select()->toArray();
        $info['region'] = implode(" ",array_column($dizhi,'name'));
        $info['city'] = implode(",",array_column($dizhi,'id'));
        $this->assign('info',$info);
        $this->assign('title','修改收货地址');
        return $this->fetch();
    }
    public function edit_ajax(Request $request,MallUserAddress $address)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $adds=$address->get($datas['id']);
            $params['wuser_id']=Session::get('wuser_id');
            $params['real_name']=$datas['real_name'];
            $params['phone']=$datas['phone'];
            $params['detail_address']=$datas['detail_address'];
            $params['is_default']=isset($datas['is_default'])?1:0;
            $city=explode(',',$datas['city']);
            $params['province']=$city[0];
            $params['city']=$city[1];
            $params['area']=$city[2];
            $params['update_time']=date("Y-m-d H:i:s");
            if($adds->save($params))
            {
                //提交数据为默认地址时进行状态更改
                if($params['is_default'])
                {
                    $new_id=$datas['id'];
                    $default=$address->where('wuser_id',Session::get('wuser_id'))->where('is_default',1)->field('id')->select()->toArray();
                    if(count($default)>1)
                    {
                        foreach ($default as $k=>$v)
                        {
                            if($v['id']!=$new_id)
                            {
                                $old_info=$address->get($v['id']);
                                $old_info->is_default=0;
                                $old_info->save();
                            }
                        }
                    }
                }
                $result['code']='200';
                $result['msg']='修改成功';
            }else
            {
                $result['code']='201';
                $result['msg']='修改失败';
            }
            return $result;
        }
    }

    public function del_address(Request $request,MallUserAddress $address)
    {
        if($request->isAjax())
        {
            $id=$request->param('id');
            $adds=$address->get($id);
            $result=$adds->delete();
            if($result)
            {
                $results['code']='200';
                $results['msg']='删除成功';
            }else
            {
                $results['code']='201';
                $results['msg']='删除失败';
            }
            return $results;
        }

    }

    public function code(MallWechatUser $wuser)
    {
        $info=$wuser->get(Session::get('wuser_id'));
        if($info->is_buy==1)
        {
            $code=$info->codes->qrcode_url;
        }else
            {
                $code='';
            }
        $this->assign('code',$code);
        $this->assign('title','我的推荐码');
        return $this->fetch();
    }

    public function referees(MallWechatUser $wuser)
    {
        $info=$wuser->get(Session::get('wuser_id'));
        $ref=$wuser->where('id',$info->second)->find();
        $this->assign('ref',$ref['nickname']);
        $this->assign('title','我的推荐人');
        return $this->fetch();
    }

    //业绩
    public function performance()
    {
        $this->assign('title','我的业绩');
        return $this->fetch();
    }
    public function performance_ajax(MallWechatUser $wuser,Request $request)
    {
        if($request->isAjax())
        {
            //加载
            $datas=$request->param();
            $winfo=$wuser->get(Session::get('wuser_id'));
            $count=$winfo->bills()->count();
            # 每页显示的数据条数
            $rev='15';
            # 获取最大页
            $max=ceil($count/$rev);
            # 获取page参数
            $page=$datas['page'];
            # 判断
            if(empty($page)){
                $page=1;
            }

            $result=$winfo->bills()->page($datas['page'],$rev)->select();
            $results['maxpage']=$max;
            $results[1]=$result;
            return $results;
        }
    }

    public function cash_info()
    {
        $this->assign('title','提现记录');
        return $this->fetch();
    }
    public function cash_info_ajax(MallWechatUser $wuser,Request $request,MallCashOut $cashOut)
    {
        if($request->isAjax())
        {
            //加载
            $datas=$request->param();
            $winfo=$wuser->get(Session::get('wuser_id'));
            if($winfo->user_id)
            {
                $count=$cashOut->where('user_id',$winfo->user_id)->count();
                # 每页显示的数据条数
                $rev='15';
                # 获取最大页
                $max=ceil($count/$rev);
                # 获取page参数
                $page=$datas['page'];
                # 判断
                if(empty($page)){
                    $page=1;
                }

                $result=$cashOut->where('user_id',$winfo->user_id)->page($datas['page'],$rev)->select();
                $results['maxpage']=$max;
                $results[1]=$result;
            }else
            {
                $results[1]='';
            }
            return $results;
        }
    }
}