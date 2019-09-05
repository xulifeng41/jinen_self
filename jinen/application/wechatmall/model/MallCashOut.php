<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/9
 * Time: 14:24
 */

namespace app\wechatmall\model;

use think\Model;

class MallCashOut extends Model
{
    public function user()
    {
        return $this->belongsTo('MallUser','user_id');
    }

    public function bank()
    {
        return $this->belongsTo('MallBank','bank_id');
    }
}