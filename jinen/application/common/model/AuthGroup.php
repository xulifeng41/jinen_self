<?php


namespace app\common\model;


use think\Model;

class AuthGroup extends Model
{
    public function managers(){
        return $this->hasMany('ManagerPc','auth_group');
    }

    public function getIsProhibitAttr($value){
        return $value==1 ? '禁止登陆' : '允许登陆';
    }

    public function setIsProhibitAttr($value){
        if ($value=='on'){
            return 2;
        }else{
            return 1;
        }
    }

    public function show_groups($now_group){
        $groups = self::where(['is_delete' => 2, 'is_prohibit' => 2])
                    ->fieldRaw('parent_id,group_concat(concat_ws("-",`id`,`name`) order by id asc separator "|") as zu')
                    ->group('parent_id')
                    ->all();
        $new_groups = [];
        foreach($groups as $k =>$v){
            $new_groups['p'.$v['parent_id']] = $v['zu'];
        }
        if(isset($new_groups['p'.$now_group['id']])){
            $childs = [];
            $this->child_groups($now_group['id'],$new_groups,$childs);
            array_unshift($childs,[$now_group['id'],$now_group['name']]);
            return $childs;
        }else{
            return [[$now_group['id'],$now_group['name']]];
        }

    }

    private function child_groups($parent_id,$groups,&$all){
        $childs1 = explode('|',$groups['p'.$parent_id]);
        foreach ($childs1 as $key => $value){
            $childs2 = explode('-',$value);
            $all[] = $childs2;
            if(isset($groups['p'.$childs2[0]])){
                $this->child_groups($childs2[0],$groups,$all);
            }

        }
    }
}