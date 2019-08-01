<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:42
 */

namespace app\common\model;


use think\Model;

class DeviceVersions extends Model
{
    public function devices()
    {
        return $this->hasMany('Devices');
    }

    public function getImageAttr($value){
        return '/static/images/'.$value;
    }

}