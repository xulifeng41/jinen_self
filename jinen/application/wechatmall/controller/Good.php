<?php
namespace app\wechatmall\controller;

use think\Request;
use think\facade\Session;
use app\wechatmall\model\MallGoods;
use app\wechatmall\model\MallCart;
class Good extends Base
{
    public function good_details(Request $request)
    {
        $id=$request->param('id');
        $this->assign('title','商品详情');
        $this->assign('good_id',$id);
        return $this->fetch();
    }

    public function add_cart(Request $request,MallCart $cart,MallGoods $goods)
    {
        if($request->isAjax())
        {
            $good_id=$request->param('id');
            $is_exist=$cart->where(['wuser_id' =>Session::get('wuser_id'), 'good_id' => $good_id])->find();
            if(empty($is_exist))
            {
                //不存在、添加
                $good_info=$goods->get($good_id);
                $params['wuser_id']=Session::get('wuser_id');
                $params['good_id']=$good_id;
                $params['good_name']=$good_info->good_name;
                $params['good_price']=$good_info->good_price;
                $params['good_nums']=1;
                $params['good_img']=$good_info->good_img;
                if($cart->save($params))
                {
                    $result['code']='200';
                    $result['msg']='添加成功';
                }else
                {
                    $result['code']='201';
                    $result['msg']='添加失败';
                }
            }else
            {
                //存在、数量+
                $is_exist['good_nums']=$is_exist->good_nums+1;
                $is_exist['update_time'] = date('Y-m-d H:i:s');
                if($is_exist->save())
                {
                    $result['code']='200';
                    $result['msg']='添加成功';
                }else
                {
                    $result['code']='201';
                    $result['msg']='添加失败';
                }
            }
            return $result;
        }
    }

    public function my_cart(MallCart $cart)
    {
        $carts=$cart->where('wuser_id' ,Session::get('wuser_id'))->select();
        $this->assign('carts',$carts);
        $this->assign('title','我的购物车');
        return $this->fetch();
    }
    public function del_cart(MallCart $cart,Request $request)
    {
        if($request->isAjax())
        {
            $cart_id=$request->param('cart_id');
            $carts=$cart->get($cart_id);
            $result=$carts->delete();
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

    public function update_cart(MallCart $cart,Request $request)
    {
        //array(2) { [7]=> string(1) "2" [6]=> string(1) "4" }
        if($request->isAjax())
        {
            $datas=$request->param();
            foreach ($datas as $k=>$v)
            {
                $len = strlen(trim($k))-1;
                if($k{0}=='n')
                {
//                    $carts[$k{$len}]=$v;
                    $cart_ids[]=substr($k,4);
                    $cart_id=substr($k,4);
                    $params['good_nums']=$v;
                    $params['update_time']=date('Y-m-d H:i:s');
                    $carts=$cart->get($cart_id);
                    $carts->force()->save($params);
                }
            }

            if($datas['type']==1)
            {
                $results['code']='200';
                $results['msg']='正在跳转';
                $results['cart_ids']=$cart_ids;
                return $results;
            }
        }
    }
}