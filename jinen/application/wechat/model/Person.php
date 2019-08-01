<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/3/30
 * Time: 14:57
 */

namespace app\wechat\model;

use think\Model;

class Person extends Model
{
    protected $pk = 'id';
    protected $table = 'customers';
    public function devices(){
        return $this->hasMany('Devices','customer_id');
    }
    public function area($value){
        $area = self::where('id', $value)->find();
        return $area->area;
    }
}