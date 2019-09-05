<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/1
 * Time: 9:44
 */

namespace app\wechatmall\model;


use think\Model;

class MallUser extends Model
{
    public function wuser(){
        return $this->hasOne('MallWechatUser','user_id');
    }


    public function bank_code(){
        return $this->hasMany('MallBank','user_id');
    }

    // 美硒后台登录验证
    public static function login_validate($params)
    {
        $user_name = $params['admin_name'];
        $user_password = $params['admin_password'];
        $user = self::where(['phone' => $user_name,'user_type'=>1])->find();
        if (empty($user)) {
            return ['status' => 201, 'msg' =>lang('empty_user')];
        }
//        if ($user['password']!=md5(md5($user_password).$user['salt'])) {
        if ($user['password']!=$user_password) {
            return ['status' => 201, 'msg' =>lang('wrong_pwd')];
        }
        return ['status' => 200, 'data' => $user];
    }
}