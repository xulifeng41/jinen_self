<?php
namespace app\wechatmall\controller;


use think\Controller;

use think\facade\Session;
use app\wechatmall\model\MallWechatUser;
class Base extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if(Session::has('wuser_id'))
        {
            $muser=new MallWechatUser();
            $info=$muser->get(Session::get('wuser_id'));
            if($info)
            {
                Session::set('user_id',$info->user_id);
                Session::set('is_sub',$info->subscribe);
            }
        }else
        {
            Session::clear();
            $weixin = new \weixin1\Wxapi();
            # 第一次获取code
            if (!isset($_GET["code"])){
                $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $jumpurl = $weixin->oauth2_authorize($redirect_url, "snsapi_userinfo", "123");
                Header("Location: $jumpurl");
            }else{
                # 后续根据access_token获得相关信息
                $access_token_oauth2 = $weixin->oauth2_access_token($_GET["code"]);
                $userinfo = $weixin->get_user_info($access_token_oauth2['openid']);
                # 根据openid查询用户数据
                $info=MallWechatUser::where('openid', $access_token_oauth2['openid'])->find();
                if($info)
                {
                    Session::set('wuser_id',$info['id']);
                    Session::set('user_id',$info['user_id']);
                    Session::set('is_sub',$userinfo['subscribe']);
                }//在关注页面未点击关注直接进入则不保存,弹出框回到关注界面
            }
        }
    }

    //分页
    public function paging($count,$info,$page)
    {

        # 每页显示的数据条数
        $rev='5';
        # 获取最大页
        $max=ceil($count/$rev);
        # 获取page参数
        $page=$page;
        # 判断
        if(empty($page)){
            $page=1;
        }

        $results['maxpage']=$max;
        $results[1]=$info;
        return $results;
    }
}