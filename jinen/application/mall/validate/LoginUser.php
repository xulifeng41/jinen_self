<?php


namespace app\mall\validate;


use think\Validate;

class LoginUser extends Validate
{
    protected $rule = [
        'phone'  =>  'require|mobile',
        'password' => 'require',
    ];

    protected $message = [
        'phone.require' => 'tel_require',
        'phone.mobile' => 'tel_right',
        'password.require' => 'pwd_require',
    ];
}