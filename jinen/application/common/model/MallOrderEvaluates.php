<?php


namespace app\common\model;


use think\Model;

class MallOrderEvaluates extends Model
{
    protected $insert = ['create_time'];
    protected $update = ['update_time'];

    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    protected function setUpdateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }
}