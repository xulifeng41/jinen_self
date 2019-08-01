<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 15:31
 */

namespace app\wechat\controller;


use think\Controller;
use app\wechat\model\Profiles;
class Profile extends Controller
{
    public function testt()
    {
        $profile = Profiles::get(1);
        echo $profile->user->name;
    }
}