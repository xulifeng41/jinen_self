<?php
namespace app\wechatmall\controller;

use app\wechatmall\model\MallRanking;
use app\wechatmall\model\MallHavebuy;

class Site extends Base
{
    public function index(MallRanking $ranking,MallHavebuy $havebuy)
    {
        $rank=$ranking->whereTime('create_time', 'week')->select();
        $buy_list=$havebuy->select();
        $this->assign('title','美硒商城');
        $this->assign('rank',$rank);
        $this->assign('list',$buy_list);
        return $this->fetch();
    }
}