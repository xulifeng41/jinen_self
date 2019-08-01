<?php


namespace app\common\model;


use think\Model;

class AdContents extends Model
{
    public function position(){
        return $this->belongsTo('AdPositions','position_id');
    }
}