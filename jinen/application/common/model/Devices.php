<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 9:43
 */

namespace app\common\model;

use think\Model;

class Devices extends Model
{
    public function costomer(){
        return $this->belongsTo('Customers','customer_id');
    }

    public function vers(){
        return $this->belongsTo('DeviceVersions','version');
    }

    public function getStatusAttr($value){
        return $value == 1 ? '运行中' : '关机';
    }

    public function getTotalTimeAttr($value){
        $days = floor($value/(3600*24));
        $str = '';
        if ($days > 0){
            $str .= $days.'天';
        }
        $yu1 = $value%(3600*24);
        $shi = floor($yu1/3600);
        if ($shi > 0){
            $str .= $shi.'小时';
        }
        $yu2 = $yu1%3600;
        $fen = floor($yu2/60);
        if ($fen > 0){
            $str .= $fen.'分';
        }
        return empty($str) ? 0 : $str;
    }

    public function getInstallerAttr($value)
    {
        return Customers::getFieldById($value, 'cus_name');
    }

}