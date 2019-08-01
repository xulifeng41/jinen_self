<?php
/**
 * Created by PhpStorm.
 * User: My XuLiFeng
 * Date: 2019/5/8
 * Time: 14:08
 */

namespace app\wechat\model;


use think\Model;

class Devices extends Model
{
    protected $pk = 'id';
    protected $table = 'devices';
    public function costomer(){
        return $this->belongsTo('Person','customer_id');
    }
}