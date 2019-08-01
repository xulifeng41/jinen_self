<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 14:17
 */

namespace app\common\model;


use think\Model;

class Menus extends Model
{
    public function getIsShowAttr($value)
    {
        if ($value==1){
            return '显示';
        }else{
            return '隐藏';
        }
    }

    public function setIsShowAttr($value){
        if($value == 'on'){
            return 1;
        }else{
            return 2;
        }
    }

    public function setIsMenuAttr($value){
        if($value == 'on'){
            return 1;
        }else{
            return 2;
        }
    }

}