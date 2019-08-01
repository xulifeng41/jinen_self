<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 16:12
 */

namespace app\wechat\model;


use think\Model;

class Comments extends Model
{
    public function users()
    {
        return $this->belongsTo('users','uids');
    }
}