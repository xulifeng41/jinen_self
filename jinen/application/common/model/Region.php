<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22
 * Time: 13:20
 */

namespace app\common\model;


use think\Model;

class Region extends Model
{
    public function province(){
        return self::field('id,name')->where('parent_id','0')->all();
    }
}