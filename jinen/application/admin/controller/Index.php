<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:47
 */

namespace app\admin\controller;


class Index extends Admin
{
    public function index(){
        return $this->fetch();
    }
}