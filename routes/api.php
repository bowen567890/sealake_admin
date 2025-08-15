<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
*/

Route::namespace('App\Http\Controllers\Api')->group(function (){


    //提供给区块那边调用的
    Route::middleware(['checkCallback'])->prefix('callback')->group(function (){
        //所有订单回调
        Route::post('callback','CallbackController@callback');
    });
    
    Route::prefix('callback')->group(function (){
        Route::post('searchPrice','CallbackController@searchPrice');
        Route::post('lpInfo','CallbackController@lpInfo');
        Route::post('lpInfov3','CallbackController@lpInfov3');
        Route::post('autoTradeDetail','CallbackController@autoTradeDetail');
        Route::post('getTransactionDetail','CallbackController@getTransactionDetail');
        
        Route::post('getChainBalance','CallbackController@getChainBalance');
        
        //获取AVE网站的价格    https://ave.ai/
        Route::post('getMhPrice1','CallbackController@getMhPrice1');
        Route::post('getMhPrice2','CallbackController@getMhPrice2');
        
        Route::post('getSpacexPrice1','CallbackController@getSpacexPrice1');
        Route::post('getSpacexPrice2','CallbackController@getSpacexPrice2');
        
        Route::get('walletRechargeLastId','CallbackController@walletRechargeLastId');
        Route::get('walletRechargeNotify','CallbackController@walletRechargeNotify');
        Route::get('walletRechargeNotifyPage','CallbackController@walletRechargeNotifyPage');
        
        Route::post('encrypterDecrypt','CallbackController@encrypterDecrypt');
        
    });
    
    Route::prefix('reptile')->group(function (){
        //爬虫
        Route::get('reptile','CallbackController@reptile');
        //爬虫
        Route::get('transfer','CallbackController@transfer');
    });
            

    //需要接口sign鉴权
    Route::middleware(['decrypt','CheckApiAuth'])->group(function (){

        Route::prefix('auth')->group(function (){
            //登录
            Route::post('login', 'AuthController@login');
        });

        //需要验证登录
        Route::middleware(['checkUserLogin'])->group(function ()
        {
            Route::prefix('user')->group(function ()
            {
                Route::post('info','UserController@info');
                Route::post('teamList','UserController@teamList');
                Route::post('usdtLog','UserController@usdtLog');
                Route::post('ticketList','UserController@ticketList');
            });

            Route::prefix('index')->group(function (){
                Route::post('index','IndexController@index');
                Route::post('ranking','IndexController@ranking');
                Route::post('pool','IndexController@pool');
            });
            
            Route::prefix('recharge')->group(function (){
                Route::post('recharge','RechargeController@recharge');
                Route::post('rechargeLog','RechargeController@rechargeLog');
            });
            
            Route::prefix('node')->group(function (){
                Route::post('config','NodeController@config');
                Route::post('openNode','NodeController@openNode');
                Route::post('openNodeLog','NodeController@openNodeLog');
            });
            
            Route::prefix('ticket')->group(function (){
                Route::post('config','TicketController@config');
                Route::post('buy','TicketController@buy');
                Route::post('buyLog','TicketController@buyLog');
                Route::post('transfer','TicketController@transfer');
            });
            Route::prefix('insurance')->group(function (){
                Route::post('invest','InsuranceController@invest');
                Route::post('investLog','InsuranceController@investLog');
                Route::post('redeem','InsuranceController@redeem');
            });
                
            
            Route::prefix('basic')->group(function (){
//                 Route::post('upload','BasicController@upload');
                Route::post('basic','BasicController@basic');
                //公告列表
                Route::post('bulletin','BasicController@bulletin');
            });

            Route::prefix('withdraw')->group(function (){
                //提现
                Route::post('index','WithdrawController@index');
                //提现列表
                Route::post('list','WithdrawController@list');
            });
        });
    });
});

