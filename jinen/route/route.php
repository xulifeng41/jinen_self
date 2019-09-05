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
    Route::rule('monitor_power','device/monitor_power')->method('get|post');
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
    Route::rule('app_versions','mobile_app_version/index')->method('get|post');
    Route::rule('add_app_versions','mobile_app_version/add_version')->method('get|post');

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

    //手机APP相关管理
    //供应商管理
    Route::rule('supplier','mobile_supplier/index')->method('get|post');
    Route::rule('supp_wait_verify','mobile_supplier/wait_verify')->method('get|post');
    Route::rule('supplier_detail','mobile_supplier/supplier_detail')->method('get|post');
    Route::rule('verify_supplier','mobile_supplier/verify_supplier')->method('get|post');
    Route::rule('close_supplier','mobile_supplier/close_supplier')->method('get|post');
    //商品管理
    Route::rule('mall_goods','mobile_good/index')->method('get|post');
    Route::rule('add_good','mobile_good/add_good')->method('get|post');
    Route::rule('good_detail','mobile_good/good_detail')->method('get|post');
    Route::rule('edit_good','mobile_good/edit_good')->method('get|post');
    Route::rule('del_good','mobile_good/del_good')->method('get|post');
    Route::rule('good_catas','mobile_good/good_cata')->method('get|post');
    Route::rule('add_good_cata','mobile_good/add_good_cata')->method('get|post');
    Route::rule('del_good_cata','mobile_good/del_good_cata')->method('get|post');
    Route::rule('all_suppliers','mobile_good/all_suppliers')->method('get|post');
    Route::rule('wait_check','mobile_good/wait_check')->method('get|post');
    Route::rule('good_recycle','mobile_good/good_recycle')->method('get|post');
    Route::rule('recycle_good','mobile_good/recycle_good')->method('get|post');
    Route::rule('destroy_good','mobile_good/destroy_good')->method('get|post');
    Route::rule('upload_img','mobile_good/upload_img')->method('get|post');
    //配送员相关
    Route::rule('delivery','mobile_delivery/index')->method('get|post');

    //资金管理
    Route::rule('cash_outs','mobile_capital/index')->method('get|post');
    Route::rule('capital_detail','mobile_capital/capital_detail')->method('get|post');
    Route::rule('supp_balance_log','mobile_capital/supp_balance_log')->method('get|post');
    Route::rule('supp_balance_detail','mobile_capital/supp_balance_detail')->method('get|post');
    Route::rule('upload_proof','mobile_capital/upload_proof')->method('get|post');
    Route::rule('user_balance_logs','mobile_capital/user_balance_logs')->method('get|post');


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
    Route::rule('sell_cus', 'personal/sell_cus')->method('get|post');
    Route::rule('sellbind', 'personal/sellbind')->method('get|post');
    Route::rule('sell_device', 'personal/sell_device')->method('get|post');
    Route::rule('sell_dajax', 'personal/sell_dajax')->method('post');
    //
    Route::rule('inster', 'personal/inster')->method('get|post');
    Route::rule('inster_ajax', 'personal/inster_ajax')->method('post');
    //设备工况监测页面
    Route::rule('working_condition', 'personal/working_condition')->method('get|post');
    //报修
    Route::rule('repairs', 'personal/repairs')->method('get|post');
    Route::rule('myrepairs', 'personal/myrepairs')->method('get|post');
    Route::rule('progress', 'personal/progress')->method('get|post');
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
//美硒公众号
Route::group('wechatmall', function () {
    //入口
    Route::rule('index', 'site/index')->method('get|post');
    //
    Route::rule('user', 'user/index')->method('get|post');
    Route::rule('address_list','user/address_list')->method('get|post');
    Route::rule('add_address','user/add_address')->method('get|post');
    Route::rule('list_ajax','user/list_ajax')->method('post');
    Route::rule('add_ajax','user/add_ajax')->method('post');
    Route::rule('edit_address','user/edit_address')->method('get|post');
    Route::rule('edit_ajax','user/edit_ajax')->method('post');
    Route::rule('del_address','user/del_address')->method('post');
    Route::rule('code', 'user/code')->method('get|post');
    Route::rule('referees', 'user/referees')->method('get|post');
    Route::rule('performance', 'user/performance')->method('get|post');
    Route::rule('performance_ajax', 'user/performance_ajax')->method('post');
    Route::rule('cash_info', 'user/cash_info')->method('get|post');
    Route::rule('cash_info_ajax', 'user/cash_info_ajax')->method('post');
    //
    Route::rule('cart', 'cart/index')->method('get|post');
    Route::rule('service', 'service/index')->method('get|post');
    //
    Route::rule('cash', 'cash/index')->method('get|post');
    Route::rule('add_bank', 'cash/add_bank')->method('get|post');
    Route::rule('add_user', 'cash/add_user')->method('get|post');
    Route::rule('bank_ajax', 'cash/bank_ajax')->method('post');
    Route::rule('user_ajax', 'cash/user_ajax')->method('post');
    Route::rule('cash_ajax', 'cash/cash_ajax')->method('post');
    Route::rule('sms', 'cash/sms')->method('post');
    Route::rule('password_ajax', 'cash/password_ajax')->method('post');
    //
    Route::rule('order', 'order/index')->method('get|post');
    Route::rule('order_list', 'order/order_list')->method('post');
    Route::rule('details', 'order/order_details')->method('get|post');
    Route::rule('create_order', 'order/create_order')->method('get|post');
    Route::rule('progress', 'order/progress')->method('post');
    Route::rule('wxpay_now', 'order/wxpay_now')->method('post');
    Route::rule('pay_check', 'order/pay_check')->method('get|post');
    Route::rule('tt', 'order/test')->method('get|post');
    //
    Route::rule('good_details', 'good/good_details')->method('get|post');
    Route::rule('add_cart', 'good/add_cart')->method('post');
    Route::rule('my_cart', 'good/my_cart')->method('get|post');
    Route::rule('del_cart', 'good/del_cart')->method('post');
    Route::rule('update_cart', 'good/update_cart')->method('post');
    //
    Route::rule('insert_ranking', 'calculate/insert_ranking')->method('get|post');
    Route::rule('havebuy', 'calculate/havebuy')->method('get|post');
    Route::rule('data_cal', 'calculate/data_cal')->method('get|post');
})->prefix('wechatmall/');
//美硒后台
Route::group('wadminmall', function () {
    //入口
    Route::rule('index', 'index/index')->method('get|post');
    Route::rule('wadmin_login', 'login/index')->method('get|post');
    Route::rule('wadmin_loginout', 'login/login_out')->method('get');
    //
    Route::rule('wadmin_edit_self_pwd', 'login/edit_pwd')->method('get|post');
    Route::rule('wadmin_edit_profile', 'login/edit_profile')->method('get|post');

    Route::rule('check_index', 'order/check_index')->method('get|post');
    Route::rule('order_check', 'order/order_check')->method('get|post');
    Route::rule('deliver', 'order/deliver')->method('get|post');
    Route::rule('order_deliver', 'order/order_deliver')->method('get|post');
    Route::rule('return_index', 'order/return_index')->method('get|post');
    Route::rule('return_check', 'order/return_check')->method('get|post');
    Route::rule('receive_check', 'order/receive_check')->method('get|post');

    Route::rule('cash_index', 'cash/cash_index')->method('get|post');
    Route::rule('check_cash', 'cash/check_cash')->method('get|post');

})->prefix('wadminmall/');
return [

];
