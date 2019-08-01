<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 15:30
 */

namespace app\wechat\controller;


use app\wechat\model\Comments;
use think\Controller;
use app\wechat\model\Users;

class User extends Controller
{
    # 测试
    public function testt()
    {
//        $user = Users::get(1);
//        echo $user->profile->email;
//        $user = Users::get(1,'profile');
// 输出Profile关联模型的email属性
//        echo $user->email;
//        echo $user->uid;
//        $article = Users::get(1);
// 获取文章的所有评论
//        dump($article->comments);
        // 查询评论超过3个的文章
//        $list = Users::has('comments','>',2)->select();
        $model=new Users();
        $result=$model->comments();
        echo '<pre>';
        print_r($result);
    }
}