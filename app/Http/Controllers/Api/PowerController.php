<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use GuzzleHttp\Client;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use App\Models\Config;
use App\Models\PowerOrderLog;
use App\Models\PowerOrder;

class PowerController extends Controller
{
    /**
     * 购买算力
     */
    public function buy(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['multiple']) || !$in['multiple'] || intval($in['multiple'])<0) {
            return responseValidateError(__('error.请选择倍数'));
        }
        
        $multiple = intval($in['multiple']);
        
        $pay_type = 1;  //支付类型1USDT
        if (isset($in['pay_type']) && $in['pay_type']) {
            $pay_type = intval($in['pay_type']);
        }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $basic_power_multiple = intval(config('basic_power_multiple'))>0 ? intval(config('basic_power_multiple')) : 1;
        if ($multiple>$basic_power_multiple) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.请选择倍数'));
        }
        
        $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
        
        $allRate = '1';
        
        $keys = [
            'power_appoint_rate1',
            'power_branch_rate',
            'power_appoint_rate2',
            'power_appoint_rate3',
            'power_appoint_address1',
            'power_appoint_address2',
            'power_appoint_address3',
            'basic_power_usdt',
            'power_multiple_num'
        ];
        
        $config = Config::query()->whereIn('key', $keys)->pluck('value','key')->toArray();
        
        //分配地址1比率 购买算力的USDT购买DOGBEE进入指定地址1
        $power_appoint_rate1 = @bcadd($config['power_appoint_rate1'], '0', 4);
        $power_appoint_rate1 = $power_appoint_rate1>=0 ? $power_appoint_rate1 : '0';
        $power_appoint_rate1 = $power_appoint_rate1>=1 ? '1' : $power_appoint_rate1;
        
        //分支地址比率 用户指定分支地址
        //向上查找最近的指定分支地址
        $power_branch_rate = '0';
        $power_branch_address = '';
        if ($user->parent_id>0 && $user->path) 
        {
            //上级信息
            $parentIds = explode('-',trim($user->path,'-'));
            $parentIds = array_filter($parentIds);
            if ($parentIds)
            {
                $pUser = User::query()
                    ->where('is_branch', 1)
                    ->whereIn('id', $parentIds)
                    ->orderBy('level', 'desc')
                    ->first(['id','wallet','is_branch']);
                if ($pUser) {
                    $power_branch_address = $pUser->wallet;
                    $power_branch_rate = @bcadd($config['power_branch_rate'], '0', 4);
                    $power_branch_rate = $power_branch_rate>=0 ? $power_branch_rate : '0';
                    $power_branch_rate = $power_branch_rate>=1 ? '1' : $power_branch_rate;
                }
            }
        }
            
        //分配地址2比率 购买算力的USDT进入指定地址2
        $power_appoint_rate2 = @bcadd($config['power_appoint_rate2'], '0', 4);
        $power_appoint_rate2 = $power_appoint_rate2>=0 ? $power_appoint_rate2 : '0';
        $power_appoint_rate2 = $power_appoint_rate2>=1 ? '1' : $power_appoint_rate2;
        
        //分配地址3比率 购买算力的USDT进入指定地址3
        $power_appoint_rate3 = @bcadd($config['power_appoint_rate3'], '0', 4);
        $power_appoint_rate3 = $power_appoint_rate3>=0 ? $power_appoint_rate3 : '0';
        $power_appoint_rate3 = $power_appoint_rate3>=1 ? '1' : $power_appoint_rate3;
        
        if (bccomp($allRate, $power_appoint_rate1, 2)>=0) {
            $allRate = bcsub($allRate, $power_appoint_rate1, 2);
        } else {
            $power_appoint_rate1 = $allRate;
            $allRate = '0';
        }
        
        if ($power_branch_address) 
        {
            if (bccomp($allRate, $power_branch_rate, 2)>=0) {
                $allRate = bcsub($allRate, $power_branch_rate, 2);
            } else {
                $power_branch_rate = $allRate;
                $allRate = '0';
            }
        }
        
        if (bccomp($allRate, $power_appoint_rate2, 2)>=0) {
            $allRate = bcsub($allRate, $power_appoint_rate2, 2);
        } else {
            $power_appoint_rate2 = $allRate;
            $allRate = '0';
        }
        
        //剩下全部给指定地址3
        $power_appoint_rate3 = $allRate;
//         var_dump($power_appoint_rate1,$power_branch_rate,$power_appoint_rate2,$power_appoint_rate3);die;
        
        $power_appoint_address1 = $config['power_appoint_address1'];
        $power_appoint_address2 = $config['power_appoint_address2'];
        $power_appoint_address3 = $config['power_appoint_address3'];
//         var_dump($power_appoint_address1,$power_branch_address,$power_appoint_address2,$power_appoint_address3);die;
        
        $basic_power_usdt = @bcadd($config['basic_power_usdt'], '0', 2);
        $basic_power_usdt = $basic_power_usdt>0 ? $basic_power_usdt : '100';
        $power_multiple_num = @bcadd($config['power_multiple_num'], '0', 2);
        $power_multiple_num = $power_multiple_num>0 ? $power_multiple_num : 1;
        
        $usdt_num = bcmul($basic_power_usdt, $multiple, 2);     //总价格USDT
        $power = bcmul($usdt_num, $power_multiple_num, 2);      //总算力
//         var_dump($usdt_num,$power);die;
       //计算分配USDT
        $usdt_num1 = bcmul($usdt_num, $power_appoint_rate1, 6);     //指定地址1   购买DOGBEE
        $usdt_branch = bcmul($usdt_num, $power_branch_rate, 6);     //分支USDT   用户分支身份
        $usdt_num2 = bcmul($usdt_num, $power_appoint_rate2, 6);     //指定地址2
        $tmpUsdt = bcadd($usdt_num1, bcadd($usdt_branch, $usdt_num2, 6), 6);
        $usdt_num3 = bcsub($usdt_num, $tmpUsdt, 6);     //指定地址3
//         var_dump($usdt_num1,$usdt_branch,$usdt_num2,$usdt_num3);die;
        
        $buy_map = [];
        if (bccomp($usdt_num, '0', 6)>0)
        {
            if (bccomp($usdt_num1, '0', 6)>0) {
                $buy_map[] = [
                    'is_buy' => 1,
                    'num' => $usdt_num1,
                    'is_user' => 0,
                    'collection_address' => $power_appoint_address1,
                ];
            }
            if (bccomp($usdt_branch, '0', 6)>0) {
                $buy_map[] = [
                    'is_buy' => 0,
                    'num' => $usdt_branch,
                    'is_user' => 1,
                    'collection_address' => $power_branch_address,
                ];
            }
            if (bccomp($usdt_num2, '0', 6)>0) {
                $buy_map[] = [
                    'is_buy' => 0,
                    'num' => $usdt_num2,
                    'is_user' => 0,
                    'collection_address' => $power_appoint_address2,
                ];
            }
            if (bccomp($usdt_num3, '0', 6)>0) {
                $buy_map[] = [
                    'is_buy' => 0,
                    'num' => $usdt_num3,
                    'is_user' => 0,
                    'collection_address' => $power_appoint_address3,
                ];
            }
        }
        
        $ordernum = get_ordernum();
        $order = new PowerOrderLog();
        $order->user_id = $user->id;
        $order->power = $power;
        $order->usdt_num = $usdt_num;
        $order->pay_type = 1;
        $order->ordernum = $ordernum;
        $order->buy_map = json_encode($buy_map);
        $order->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 2;    //订单类型1余额提币2购买算力3签到触发
        $OrderLog->save();
        
        $is_two = 0;
        $pay_data[] = [
            'total' => $usdt_num,
            'contract_address' => $usdtCurrency->contract_address,
            'branch_address' => $power_branch_address,  //指定分支地址 其他合约写死 分配比率合约那边算
            'list' => $buy_map
        ];
        
        
        $data = [
            'remarks' => $ordernum,
            'is_chain' => 1,
            'is_two' => $is_two,
            'pay_data' => $pay_data
        ];
        $MyRedis->del_lock($lockKey);
        return responseJson($data);
    }
    
    public function buyLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = PowerOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['power','usdt_num','hash','created_at'])
            ->toArray();
//         if ($list) {
//             foreach ($list as &$v) {
//             }
//         }
        return responseJson($list);
    }
    
}
