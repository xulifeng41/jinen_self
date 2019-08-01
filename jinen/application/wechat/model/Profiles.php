<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 15:27
 */

namespace app\wechat\model;


use think\Model;

class Profiles extends Model
{
    public function user()
    {
        return $this->belongsTo('Users','uid');
    }
}