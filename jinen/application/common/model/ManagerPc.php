<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:43
 */

namespace app\common\model;


use think\Model;

class ManagerPc extends Model
{
    public function auth(){
        return $this->belongsTo('AuthGroup','auth_group');
    }

    //用户自己的公司
    public function corporation(){
        return $this->hasOne('Company','legal_person');
    }

    //用户所属公司
    public function company(){
        return $this->belongsTo('Company','company_id');
    }

    public function getIsDeleteAttr($value){
        return $value == 1 ? '允许登陆' : '<span style="color: red;">禁止登陆</span>';
    }

    public function setIsDeleteAttr($value){
        return $value == 'on' ? 1 : 2;
    }

    // 用户登录验证
    public static function login_validate($params)
    {
        $user_name = $params['admin_name'];
        $user_password = $params['admin_password'];
        $user = self::where(['user_name' => $user_name, 'is_delete' => 1])->find();
        if (empty($user)) {
            return ['status' => 201, 'msg' =>lang('empty_user')];
        }
        if ($user['password']!=md5(md5($user_password).$user['salt'])) {
            return ['status' => 201, 'msg' =>lang('wrong_pwd')];
        }
        return ['status' => 200, 'data' => $user];
    }
}