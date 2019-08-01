<?php
namespace app\adminmall\controller;

use app\common\model\MallSuppliers;
use think\Request;
use think\facade\Session;
use app\common\model\MallOrders;
class Index extends Adminm
{
    public function index()
    {
        return $this->fetch();
    }

    public function console(MallSuppliers $suppliers,MallOrders $orders)
    {
        $data['num']=$orders->num();
        $data['balance']=$suppliers->balance();
        return $data;
    }
}
