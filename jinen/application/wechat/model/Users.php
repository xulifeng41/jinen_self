<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 15:27
 */

namespace app\wechat\model;


use think\Model;

class Users extends Model
{
    public function profile()
    {
        return $this->hasOne('Profiles','uid','id')->field('id,email');
//        return $this->hasOne('Profiles','uid')->bind([
//            'email',
//            'uid'	=> 'uid',
//        ]);
    }
    public function comments()
    {
        return $this->hasMany('Comments','uids','id');
    }
}