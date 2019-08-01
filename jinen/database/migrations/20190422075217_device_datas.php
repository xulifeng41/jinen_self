<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class DeviceDatas extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        for($i=1;$i<101;$i++){
            $table = 'device_datas_'.$i;
            $device_datas = $this->table($table,array('engine'=>'MyISAM'));
            $device_datas->addColumn('device_id','integer',['comment'=>'设备号']);
            $device_datas->addColumn('soot','decimal',['precision' => '5','scale' => '2','comment'=>'油烟排放量']);
            $device_datas->addColumn('pellet','decimal',['precision' => '10','scale' => '2','comment'=>'颗粒物', 'null' => true]);
            $device_datas->addColumn('not_methane','decimal',['precision' => '10','scale' => '2','comment'=>'非甲烷气体', 'null' => true]);
            $device_datas->addColumn('fan_status','integer',['limit' => MysqlAdapter::INT_TINY, 'comment'=>'风机运行状态', 'default' => 1]);
            $device_datas->addColumn('voltage','integer',['comment'=>'电压', 'null' => true]);
            $device_datas->addColumn('wind_speed','integer',['comment'=>'风速', 'null' => true]);
            $device_datas->addColumn('fire_controll','integer',['limit' => MysqlAdapter::INT_TINY,'comment'=>'火警监测，1：报警，2：正常', 'default' => 2]);
            $device_datas->addColumn('leakage','integer',['limit' => MysqlAdapter::INT_TINY,'comment'=>'天然气泄漏，1：泄漏，2：正常', 'default' => 2]);
            $device_datas->addColumn('create_time','datetime');
            $device_datas->create();
        }
    }
}
