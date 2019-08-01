<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 10:05
 */

namespace app\admin\validate;


use think\Validate;

class Manager extends Validate
{
    protected $rule = [
        'name'  =>  'require|max:25|token',
    ];

}