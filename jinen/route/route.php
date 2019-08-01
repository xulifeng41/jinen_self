<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

//后台路由
Route::group('admin', function () {
    Route::rule('/', 'index/index')->method('get');
//    后台登录
    Route::rule('login', 'users/login')->method('get|post');
    Route::rule('login_by_token', 'users/login_by_token')->method('get');
//    退出登录
    Route::rule('loginout', 'users/login_out')->method('get');
    Route::rule('edit_self_pwd', 'users/edit_pwd')->method('get|post');
    Route::rule('edit_profile', 'users/edit_profile')->method('get|post');

//    登录验证码
//    Route::rule('verify', 'users/verify')->method('get');
//    菜单列表
    Route::rule('admin_menu','admin_menu/index')->method('get|post');
//    添加菜单
    Route::rule('add_menu','admin_menu/add_menu')->method('get|post');
    Route::rule('del_menu','admin_menu/del')->method('get|post');
//    设备
    Route::rule('device_list','device/index')->method('get|post');
    Route::rule('edit_device','device/edit')->method('get|post');
    Route::rule('monitor1','device/monitor_datas')->method('get|post');
    Route::rule('monitor2','device/monitor_datas_table')->method('get|post');
    Route::rule('close_device','device/close')->method('get|post');
    Route::rule('env_show','device/env_show')->method('post');

    Route::rule('region','device/region')->method('get|post');
    Route::rule('version','version/index')->method('get|post');
    Route::rule('add_version','version/add_version')->method('get|post');
    Route::rule('del_version','version/del')->method('get|post');
    Route::rule('upload','version/upload')->method('get|post');
    //客户
    Route::rule('customers','customer/sellers')->method('get|post');
    Route::rule('cusindex','customer/index')->method('get|post');
    Route::rule('editcus','customer/edit')->method('get|post');
    //系统设置
    Route::rule('setting','system/index')->method('get|post');
    Route::rule('other','other/index')->method('get|post');
    //杂项
    Route::rule('add_custom','other/add_custom')->method('get|post');
    Route::rule('add_device','other/add_device')->method('get|post');
//    权限组
    Route::rule('groups','permission/index')->method('get|post');
    Route::rule('add_group','permission/add_group')->method('get|post');
    Route::rule('edit_group','permission/edit_group')->method('get|post');
    Route::rule('del_group','permission/del')->method('get|post');
//    管理员
    Route::rule('managers','manager/index')->method('get|post');
    Route::rule('add_manager','manager/add_manager')->method('get|post');
    Route::rule('edit_manager','manager/edit')->method('get|post');
    Route::rule('edit_manager_pwd','manager/edit_pwd')->method('get|post');
    Route::rule('del_manager','manager/del_manager')->method('get|post');
    //管理员操作日志
    Route::rule('managerlogs','manage_log/index')->method('get|post');
    //测试微信
    Route::rule('test_weixin','other/test_weixin')->method('get|post');
    //公司管理
    Route::rule('corporations','corporation/index')->method('get|post');
    Route::rule('add_corporation','corporation/add_corporation')->method('get|post');
    Route::rule('edit_corporation','corporation/edit_company')->method('get|post');
    Route::rule('del_corporation','corporation/del_company')->method('get|post');

})->prefix('admin/');

Route::rule('get_token', 'index/index/get_token')->allowCrossDomain();

//wechat路由
Route::group('wechat', function () {
    //入口
    Route::rule('personal', 'personal/index')->method('get|post');
    //安装员添加设备/业务员更新客户信息
    Route::rule('insert', 'personal/insert')->method('post');
    //ajax
    Route::rule('pregphone', 'personal/pregphone')->method('post');
    Route::rule('address', 'personal/address')->method('post');
    Route::rule('sms', 'personal/sms')->method('post');
    Route::rule('pregsms', 'personal/pregsms')->method('post');
    Route::rule('selectuser', 'personal/selectuser')->method('get|post');
    Route::rule('searchajax', 'personal/searchajax')->method('get|post');
    Route::rule('dcodeajax', 'personal/dcodeajax')->method('get|post');
    Route::rule('repairsajax', 'personal/repairsajax')->method('get|post');
    Route::rule('repsajax', 'personal/repsajax')->method('get|post');
    //注册绑定手机
    Route::rule('useradd', 'personal/useradd')->method('post');
    //个人中心详情
    Route::rule('mydevice', 'personal/mydevice')->method('get|post');
    Route::rule('add', 'personal/add')->method('get|post');
    //执行客户修改信息/业务员修改客户信息/安装员修改设备型号
    Route::rule('update', 'personal/update')->method('get|post');
    Route::rule('edit', 'personal/edit')->method('get|post');
    //图表
    Route::rule('jiance', 'personal/jiance')->method('get|post');
    Route::rule('jianceajax', 'personal/jianceajax')->method('get|post');
    //业务员我售出的设备/安装员安装的设备/客户我的设备ajax数据
    Route::rule('usersajax', 'personal/usersajax')->method('get|post');
    //业务员选择页面
    Route::rule('sellbind', 'personal/sellbind')->method('get|post');
    //报修
    Route::rule('repairs', 'personal/repairs')->method('get|post');
    Route::rule('myrepairs', 'personal/myrepairs')->method('get|post');
    Route::rule('progress', 'personal/progress')->method('get|post');
    Route::rule('test', 'personal/test')->method('get|post');
})->prefix('wechat/');
//卖家商城路由
Route::group('adminmall', function () {
    //入口
    Route::rule('/', 'index/index')->method('get');
    Route::rule('console', 'index/console')->method('get|post');
//    后台登录
    Route::rule('login', 'suppliers/login')->method('get|post');
//    退出登录
    Route::rule('loginout', 'suppliers/login_out')->method('get');
    //
    Route::rule('edit_self_pwd', 'suppliers/edit_pwd')->method('get|post');
    Route::rule('edit_profile', 'suppliers/edit_profile')->method('get|post');
    //商品管理
    Route::rule('goods', 'goods/index')->method('get|post');
    Route::rule('add_goods','goods/add_goods')->method('get|post');
    Route::rule('edit_goods','goods/edit_goods')->method('get|post');
    Route::rule('show_imgs','goods/show_imgs')->method('get|post');
    Route::rule('del_goods','goods/del')->method('get|post');
    Route::rule('upload','goods/upload')->method('get|post');
    Route::rule('uploads','goods/uploads')->method('get|post');
    Route::rule('recycle','goods/recycle')->method('get|post');
    Route::rule('recover','goods/recover')->method('get|post');
    Route::rule('is_sale','goods/is_sale')->method('post');
    Route::rule('is_check','goods/is_check')->method('get|post');
    Route::rule('orders', 'orders/index')->method('get|post');
    Route::rule('edit_orders', 'orders/edit_orders')->method('get|post');
    Route::rule('info_orders', 'orders/info_orders')->method('get|post');
    //
    Route::rule('bill', 'bill/index')->method('get|post');
    Route::rule('cash_out', 'bill/cash_out')->method('get|post');
    Route::rule('cash_check', 'bill/cash_check')->method('get|post');
    //

})->prefix('adminmall/');
Route::group('wechatmall', function () {
    //入口
    Route::rule('index', 'user/index')->method('get|post');
})->prefix('wechatmall/');
return [

];
