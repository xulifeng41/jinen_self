<?php


namespace app\common\model;


use think\Model;

class Company extends Model
{
    //公司属于哪个人
    public function legaler(){
        return $this->belongsTo('ManagerPc','legal_person');
    }

    //公司有哪些员工
    public function personnels(){
        return $this->hasMany('ManagerPc','company_id');
    }

    public function getLogoAttr($value){
        return '/static/images/'.$value;
    }

    public function setLogoAttr($value){
        return str_replace('/static/images/','',$value);
    }

}