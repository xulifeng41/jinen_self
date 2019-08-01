<?php


namespace app\mall\controller;


use app\common\model\Customers;
use app\common\model\MallCatagories;
use app\common\model\MallGoods;
use app\common\model\MallOrders;
use app\common\model\MallShoppingCart;
use rsa\Sign;
use think\Controller;

class Good extends Controller
{
    private $params = [];
    private $user = '';

    protected $beforeActionList = [
        'decrypt' =>['except'=>'good_cats']
    ];

    /*前置操作（验签）*/
    protected function decrypt()
    {
        $sign = new Sign();
        $res = $sign->create_sign();
        if ($res['code']==201){
            return $res;
        }
        $this->params = $res['params'];

        $params = $res['params'];
        $user = Customers::field('id,is_supplier,is_agent')
            ->where(['token' => $params['token']])
            ->find();
        if (empty($user)){
            return err_return(lang('login_info_err'));
        }
        $this->user = $user;

    }

    /*商品分类*/
    public function good_cats(MallCatagories $categories){
        $cats = $categories->field('id,cat_name')
                ->where(['is_delete'=>0])
                ->order(['sort'=>'asc'])
                ->all();
        return right_return($cats);
    }

    /*
     * 商品分类列表（按地区进行区分）
     * $cat_id 分类id
     * $province 省
     * $city 市
     * $area 县
     * */
    public function good_list(MallCatagories $catagories){
        $params = $this->params;
        $cat_id = isset($params['cat_id']) ? $params['cat_id'] : 1;
        if ($cat_id==4){
            $province = $params['province'];
            $city = $params['city'];
            $area = $params['area'];
            $where = ['is_sale' => 1, 'is_delete' => 0, 'is_check' => 1, 'province' => $province, 'city' => $city, 'area' => $area];
        }else{
            $where = ['is_sale' => 1, 'is_delete' => 0, 'is_check' => 1];
        }

        $page = empty($params['page']) ? 1 : $params['page'];
        $catagory = $catagories->with(['goods'=>function($query) use ($page,$where
        ){
            $query->field('id,good_name,good_img,supplier_id,price,unit,specs,cata_id')->where($where)->page($page,10)->order(['sort' => 'asc']);
        }])->get($cat_id);
        if (!empty($catagory->goods)){
            $list = $catagory->goods;
            if ($list->isEmpty()){
                return err_return(lang('cat_err'));
            }else{
                return right_return($list);
            }
        }else{
            return err_return(lang('cat_err'));
        }

    }

    /*我的购物车
     * 商品按供应商不同分组，并生成数量数组
     * $user_id 用户id
    */
    public function my_cart(){
        $goods = $this->user->cart_goods;
        $params = $this->params;
        $page = isset($params['page']) ? $params['page'] : 1;
        $length = 5;
        $start = ($page-1)*$length;

        if (!$goods->isEmpty()){
            $goods = $goods->toArray();
//            购物车商品排序
            uasort($goods,function($a,$b){
                if (empty($a['update_time'])&&empty($b['update_time'])){
                    return (strtotime($a['create_time']) < strtotime($b['create_time'])) ? 1 : -1;
                }
                if (empty($a['update_time'])&&!empty($b['update_time'])){
                    return 1;
                }
                if (!empty($a['update_time'])&&empty($b['update_time'])){
                    return -1;
                }
                if (!empty($a['update_time'])&&!empty($b['update_time'])){
                    return (strtotime($a['update_time']) < strtotime($b['update_time'])) ? 1 : -1;
                }
            });
//            统计所有供应商
            $suppliers1 = array_column($goods,'supplier_id');
            $suppliers1 = array_unique($suppliers1);
            //截取供应商实现分页
            $suppliers = array_slice($suppliers1,$start,$length);
//            按不同供应商分组
            $return_goods = array();
            $nums = array();
            foreach($suppliers as $key => $value){
                $ii = 0;
                foreach ($goods as $kg => $vg){
                    if ($value==$vg['supplier_id']){
                        $return_goods[$key]['sup_name'] = $vg['supplier_name'];
                        $return_goods[$key]['goods'][$ii] = $vg;
                        $nums[$key][$ii] = $vg['good_nums'];
                        $ii++;
                    }
                }
                unset($kg);
                unset($vg);
            }
            unset($key);
            unset($value);
            return right_return(['nums' => $nums, 'goods' => $return_goods]);

        }else{
            return err_return(lang('empty_cart'));
        }
    }

    /* 删除购物车商品
     * */
    public function del_cart_good(MallShoppingCart $mallShoppingCart){
        $user = $this->user;
        $params = $this->params;
        $goods = $params['goods'];
        $orders = $mallShoppingCart->whereIn('id',$goods)->all();
        foreach ($orders as $key => $order){
            if ($order['customer_id']==$user['id']){
                $order->delete();
            }
            unset($orders[$key]);
        }
        unset($key);
        unset($order);

        if ($orders->isEmpty()){
            return right_return([],lang('action_ok'));
        }else{
            return err_return(lang('action_err'));
        }
    }

    /*添加购物车
        good_id: 商品id
        user_id:用户id
    */
    public function add_cart(MallShoppingCart $cart, MallGoods $goods){
        $params = $this->params;
        $user_id = $this->user->id;
        $good_id = $params['good_id'];
        //查看购物车是否存在该商品
        $is_exist = $cart->where(['customer_id' =>$user_id, 'good_id' => $good_id])->find();
        if (empty($is_exist)){
//            如果没有，直接添加
            $field = 'id,good_name,good_img,supplier_id,price,specs';
            $good = $goods->with(['supp' => function($query){
                $query->field('id,sup_name');
            }])->field($field)->get($good_id);
            $cart1 = $good->toArray();
            $cart1['good_id'] = $cart1['id'];
            $cart1['good_price'] = $cart1['price'];
            $cart1['good_specs'] = $cart1['specs'];
            $cart1['supplier_name'] = $cart1['supp']['sup_name'];
            $cart1['customer_id'] = $user_id;
            unset($cart1['supp']['sup_name']);
            unset($cart1['id']);
            unset($cart1['price']);
            unset($cart1['specs']);
            if ($cart->save($cart1)){
                return right_return('',lang('add_cart_success'));
            }else{
                return err_return(lang('action_err'));
            }
        }else{
//            如果有，修改数量加1
            $is_exist['good_nums'] = $is_exist['good_nums'] + 1;
            $is_exist['update_time'] = date('Y-m-d H:i:s');
            if ($is_exist->save()){
                return right_return('',lang('add_cart_success'));
            }else{
                return err_return(lang('action_err'));
            }
        }
    }

    /* 商品详情
     * $good_id 商品id
     * */
    public function good_detail(MallGoods $mallGoods){
        $params = $this->params;
        $good = $mallGoods->with('supp')->get($params['good_id']);
        if (empty($good)){
            return err_return(lang('err_good'));
        }else{
            return right_return($good);
        }
    }

}