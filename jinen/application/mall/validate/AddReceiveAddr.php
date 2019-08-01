<?php


namespace app\mall\validate;


use think\Validate;

class AddReceiveAddr extends Validate
{
    protected $rule = [
        'receiver_phone'  =>  'require|mobile',
        'receiver_name' => 'require',
        'province' => 'require',
        'city' => 'require',
        'area' => 'require',
        'detail_address' => 'require',
    ];

    protected $message = [
        'receiver_phone.require' => 'tel_require',
        'receiver_phone.mobile' => 'tel_right',
        'receiver_name.require' => 'receiver_name_require',
        'province.require' => 'diyu_require',
        'city.require' => 'diyu_require',
        'area.require' => 'diyu_require',
        'detail_address.require' => 'diyu_require',
    ];
}