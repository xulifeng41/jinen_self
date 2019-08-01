<?php


namespace app\mall\controller;


use app\common\model\AdContents;
use app\common\model\MallGoods;
use rsa\Sign;
use think\Controller;

class Advertisement extends Controller
{
    private $params = [];


    protected $beforeActionList = [
        'decrypt',
    ];

    /*前置操作（验签）*/
    protected function decrypt()
    {
        $sign = new Sign();
        $res = $sign->create_sign();
        if ($res['code']!=200){
            return err_return($res['msg']);
        }
        $this->params = $res['params'];
    }


    /*首页轮播图
     * 按区域进行区分
    */
    public function index_slide(AdContents $adContents){
        $params = $this->params;
        $positon_id = $params['positon_id'];
        $province = $params['province'];
        $city = $params['city'];
        $area = $params['area'];
        $contents = $adContents->field('image')
            ->where(['position_id' => $positon_id, 'is_delete' => 0, 'province' =>$province, 'city' => $city, 'area' => $area])
            ->limit('0','3')
            ->order(['sort' => 'asc'])
            ->all();
        return right_return($contents->toArray());
    }

    /*首页广告图片
     * 只有一个张图片
    */
    public function one_image_ad(AdContents $adContents){
        $params = $this->params;
        $positon_id = $params['positon_id'];
        $province = $params['province'];
        $city = $params['city'];
        $area = $params['area'];
        $contents = $adContents->field('image')
            ->where(['position_id' => $positon_id, 'is_delete' => 0, 'province' =>$province, 'city' => $city, 'area' => $area])
            ->limit('0','1')
            ->order(['sort' => 'asc'])
            ->all();
        return right_return($contents->toArray());
    }

    /* 首页热销产品
    * 默认成杭州地区吧
    * */
    public function hot_goods(MallGoods $mallGoods){
        $params = $this->params;
        $province = $params['province'];
        $city = $params['city'];
        $area = $params['area'];
        $where = ['is_sale' => 1, 'is_delete' => 0, 'is_check' => 1, 'is_hot' => 1, 'province' => $province, 'city' => $city, 'area' => $area];
        $field = 'id,good_name,good_img,price,unit,specs';
        $goods = $mallGoods->where($where)
            ->field($field)
            ->limit(6)
            ->order(['update_time' => 'desc','sort' => 'asc'])
            ->all();
        if (!$goods->isEmpty()){
            return right_return($goods);
        }else{
            $where = ['is_sale' => 1, 'is_delete' => 0, 'is_check' => 1, 'province' => $province, 'city' => $city, 'area' => $area];
            $goods = $mallGoods->where($where)
                ->field($field)
                ->limit(6)
                ->order(['update_time' => 'desc', 'sort' => 'asc'])
                ->all();
            if (!$goods->isEmpty()){
                return right_return($goods);
            }else{
                return err_return('');
            }
        }
    }

    /* 首页金恩油烟一体机展示
    * */
    public function own_goods(MallGoods $mallGoods){
        $where = ['is_sale' => 1, 'is_delete' => 0, 'is_check' => 1, 'cata_id' => 1, 'supplier_id' => 1];
        $field = 'id,good_name,good_img,price,unit,specs';
        $goods = $mallGoods->where($where)
            ->field($field)
            ->order(['create_time' => 'desc'])
            ->all();
        if (!$goods->isEmpty()){
            return right_return($goods);
        }else{
            return err_return('');
        }
    }


}