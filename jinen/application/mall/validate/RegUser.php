<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 10:05
 */

namespace app\mall\validate;


use think\Validate;

class RegUser extends Validate
{
    protected $rule = [
        'phone'  =>  'require|mobile',
        'password' => 'require',
        'code' => 'require|length:4',
        'invite_code' => 'require',
    ];

    protected $message = [
        'phone.require' => 'tel_require',
        'phone.mobile' => 'tel_right',
        'password.require' => 'pwd_require',
        'code.require' => 'code_require',
        'code.length' => 'code_err',
        'invite_code.require' => 'invite_require',


    ];

}