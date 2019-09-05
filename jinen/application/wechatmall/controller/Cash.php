<?php
namespace app\wechatmall\controller;

use think\facade\Session;
use think\Request;
use app\wechatmall\model\MallWechatUser;
use app\wechatmall\model\MallUser;
use app\wechatmall\model\MallBank;
use aliyun\SendSms;
use app\wechatmall\model\ShortMessage;
use app\wechatmall\model\MallCashOut;
class Cash extends Base
{
    //页面
    public function index(MallWechatUser $wuser,MallUser $user,MallBank $bank)
    {
        $winfo=$wuser->get(Session::get('wuser_id'));
        if($winfo->user_id)
        {
            $uinfo=$user->get($winfo->user_id);
            $result=$uinfo->bank_code()->select()->toarray();
            if($result)
            {
                $this->assign('is_bind',1);
            }else
            {
                $this->assign('is_bind',0);
            }
        }else
        {
            $this->assign('is_bind',0);
        }
        if(Session::has('user_id'))
        {
            $bank_info=$bank->field(['id','concat(bank_code,"-",account)'=>'bank'])->where('user_id',Session::get('user_id'))->select();
            foreach ($bank_info as $k=>$v)
            {
                $binfo[$k]['title']=$v['bank'];
                $binfo[$k]['value']=$v['id'];
            }
        }else
            {
                $binfo=[];
            }

        $this->assign('winfo',$winfo);
        $this->assign('binfo',json_encode($binfo));
        $this->assign('title','填写信息');
        return $this->fetch();
    }

    public function cash_ajax(Request $request,MallWechatUser $wuser,MallCashOut $cash)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $winfo=$wuser->get(Session::get('wuser_id'));
            $balance=$winfo->balance;

            if($balance<$datas['cash'])
            {
                $result['code']='201';
                $result['msg']='与现存佣金金额不符';
                return $result;
            }

            $params['bank_id']=$datas['bank_id'];
            $params['cash']=$datas['cash'];
            $params['actual_cash']=$datas['cash'];
            $params['user_id']=$winfo->user_id;
            $params['status']=0;
            if($cash->save($params))
            {
                $winfo->balance=$balance-$datas['cash'];
                $winfo->update_time=date("Y-m-d H:i:s");
                $winfo->save();
                $result['code']='200';
                $result['msg']='申请成功,正在审核';
            }else
            {
                $result['code']='202';
                $result['msg']='申请失败';
            }
            return $result;
        }
    }
    public function add_bank()
    {
        $this->assign('title','添加银行卡');
        return $this->fetch();
    }

    public function bank_ajax(Request $request,MallBank $bank)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $datas['user_id']=Session::get('user_id');
            if($bank->save($datas))
            {
                $result['code']='200';
                $result['msg']='添加成功';
            }else
            {
                $result['code']='201';
                $result['msg']='添加失败';
            }
            return $result;
        }
    }
    public function add_user()
    {
        $this->assign('title','账号绑定');
        return $this->fetch();
    }

    public function user_ajax(MallWechatUser $wuser,MallUser $user,Request $request,ShortMessage $message)
    {
        if($request->isAjax())
        {
            $datas=$request->param();
            $message_info=$message->where('phone',$datas['phone'])->find();
            if($message_info)
            {
                if($message_info['code']==$datas['code'])
                {
                    if(time()>$message_info['expire_time'])
                    {
                        $result['code']='201';
                        $result['msg']='验证码已过期';
                    }else
                    {
                        $rand = rand(1000,9999);
                        $params['phone']=$datas['phone'];
                        $params['password']=md5(md5($datas['password']).$rand);
                        $params['salt'] = $rand;
                        if($user->save($params))
                        {
                            $winfo=$wuser->get(Session::get('wuser_id'));
                            $winfo->user_id=$user->id;
                            if($winfo->save())
                            {
                                Session::set('user_id',$user->id);
                                $result['code']='200';
                                $result['msg']='绑定成功';
                            }
                        }
                    }
                }else
                {
                    $result['code']='202';
                    $result['msg']='验证码错误';
                }
            }else
            {
                $result['code']='203';
                $result['msg']='上述手机号未进行验证';
            }
            return $result;
        }
    }

    public function sms(Request $request)
    {
        if($request->isAjax())
        {
            $phone = $request->param('phone');
            $send = new SendSms();
            return $send->sms($phone);
        }
    }

    public function password_ajax(Request $request,MallUser $user)
    {
        if($request->isAjax())
        {
            $password = $request->param('password');
            $user_info=$user->get(Session::get('user_id'));
            $rand=$user_info->salt;
            if(md5(md5($password).$rand)==$user_info->password)
            {
                $result['code']='200';
                $result['msg']='密码验证成功';
            }else
            {
                $result['code']='201';
                $result['msg']='密码验证失败';
            }
            return $result;
        }
    }
}