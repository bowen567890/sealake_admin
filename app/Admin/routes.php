<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->resource('users', 'UserController');
    $router->resource('tree', 'UserTreeController');
    $router->resource('recharge', 'RechargeController');
    $router->resource('banner', 'BannerController');
    $router->resource('bulletin', 'BulletinController');
    $router->resource('withdraw', 'WithdrawController');
    $router->resource('main_currency','MainCurrencyController');
    
    $router->resource('rank_config','RankConfigController');
    $router->resource('depth_config','DepthConfigController');
    $router->resource('user_usdt','UserUsdtController');
    $router->resource('rank_conf','RankConfigController');
    $router->resource('deep_config','DeepConfigController');
    
    
    $router->resource('ticket_currency','TicketCurrencyController');
    $router->resource('news','NewsController');
    
    $router->resource('user_dogbee','UserDogbeeController');
    $router->resource('user_usdt','UserUsdtController');
    $router->resource('user_power','UserPowerController');
    $router->resource('lucky_pool','LuckyPoolController');
    $router->resource('lucky_log','LuckyLogController');
    
    $router->resource('sign_config','SignConfigController');
    $router->resource('sign_order','SignOrderController');
    $router->resource('power_order','PowerOrderController');
    $router->resource('see_config','SeeConfigController');
    $router->resource('node_pool','NodePoolController');
    $router->resource('manage_config','ManageRankConfigController');
    $router->resource('manage_operate_log','ManageOperateLogController');
    
    $router->resource('merchant_order','MerchantOrderController');
    $router->resource('point_config','PointConfigController');
    
    $router->resource('point_order','PointOrderController');
    $router->resource('user_point','UserPointController');
    
    $router->resource('normal_nodeorder','NormalNodeOrderController');
    $router->resource('super_nodeorder','SuperNodeOrderController');
    
    
    $router->any('auth/extensions',function (){
        die();
    });
});
