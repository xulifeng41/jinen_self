<?php


namespace app\common\model;


use think\Model;

class AdPositons extends Model
{
    public function content(){
        return $this->hasMany('AdContents','position_id');
    }
}