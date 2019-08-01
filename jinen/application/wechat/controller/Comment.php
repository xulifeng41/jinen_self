<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 16:12
 */

namespace app\wechat\controller;


use think\Controller;
use app\wechat\model\Comments;
class Comment extends Controller
{
    public function testt()
    {
        $com = Comments::get(1);
        echo '<pre>';
        print_r($com->users);
    }
}