<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\Banner;
use App\Models\Bulletin;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use App\Models\PowerConf;
use GuzzleHttp\Client;
use App\Models\Withdraw;
use App\Models\SignConfig;
use App\Models\SignOrderLog;
use App\Models\SignOrder;
use App\Models\SyncPower;

class SignController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        $data['last_sign_time'] = $user->last_sign_time;
        $data['sign_interval_day'] = intval(config('sign_interval_day'));
        $data['sign_config'] = SignConfig::GetListCache();
        return responseJson($data);
    }
    
    /**
     * 签到
     */
    public function sign(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['id']) || intval($in['id'])<=0) {
            return responseValidateError(__('error.请选择签到价格'));
        }
        $id = intval($in['id']);
        
        $pay_type = 1;  //支付类型1USDT
        if (isset($in['pay_type']) && $in['pay_type']) {
            $pay_type = intval($in['pay_type']);
        }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $user = User::query()->where('id', $user->id)->first(['id','power','wallet','last_sign_time']);
        
        $time = time();
        if ($user->last_sign_time) 
        {
            $sign_interval_day = intval(config('sign_interval_day'));
            $sign_interval_day = $sign_interval_day>0 ? $sign_interval_day : 7;
            
            $last_sign_time = strtotime($user->last_sign_time);
            $next_sign_time = $last_sign_time+86400*$sign_interval_day;
            if ($next_sign_time>=$time) {
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.未到下一次签到时间'));
            }
        }
        
        if (bccomp($user->power, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.算力不足'));
        }
        
        $SignConfig = SignConfig::query()->where('id', $id)->where('is_del', 0)->first();
        if (!$SignConfig) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.请选择签到价格'));
        }
        
        $sign_power_rate = $SignConfig->sign_power_rate;
        $sign_power = bcmul($user->power, $sign_power_rate, 6);
        if (bccomp($sign_power, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.算力不足'));
        }
        
        
        $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
        
        $usdt_num = $SignConfig->price;
        
        $collection_address = $user->wallet; //归集地址 帮用户购买DOGBEE
        
        $pay_type = 1;  //支付类型1USDT(链上)
        
        $is_chain = 0;
        $ordernum = get_ordernum();
        
        $datetime = date('Y-m-d H:i:s', $time);
        
        if (bccomp($usdt_num, '0', 2)>0) 
        {
            $SignOrderLog = new SignOrderLog();
            $SignOrderLog->ordernum = $ordernum;
            $SignOrderLog->user_id = $user->id;
            $SignOrderLog->usdt_num = $usdt_num;
            $SignOrderLog->sign_power = $sign_power;
            $SignOrderLog->pay_type = $pay_type;   //支付类型1USDT(链上)
            //         $SignOrderLog->coin_price = $spacexCurrency->rate;
            $SignOrderLog->save();
            
            $OrderLog = new OrderLog();
            $OrderLog->ordernum = $ordernum;
            $OrderLog->user_id = $user->id;
            $OrderLog->type = 3;    //订单类型1余额提币2购买算力3签到触发
            $OrderLog->save();
            
            $is_chain = 1;
        } 
        else 
        {
            $signKey = 'signPowerBack:'.$user->id;
//                                                             $MyRedis->del_lock($signKey);
            $retRes = $MyRedis->setnx_lock($signKey, 30);
            if(!$retRes){
                $MyRedis->del_lock($lockKey);
                return responseValidateError(__('error.操作频繁'));
            }
            
            //生成订单记录
            $SignOrder = new SignOrder();
            $SignOrder->ordernum = $ordernum;
            $SignOrder->user_id = $user->id;
            $SignOrder->usdt_num = $usdt_num;
            $SignOrder->sign_power = $sign_power;
            $SignOrder->pay_type = $pay_type;
            $SignOrder->hash = '';
            $SignOrder->save();
            
            $user->last_sign_time = $datetime;
            $user->save();
            
            $SyncPower = new SyncPower();
            $SyncPower->user_id = $user->id;
            $SyncPower->order_id = $SignOrder->id;
            $SyncPower->type = 2;  //类型1购买算力2算力签到
            $SyncPower->usdt = $usdt_num;
            $SyncPower->power = $sign_power;
            $SyncPower->ordernum = $ordernum;
            $SyncPower->save();
            
            $MyRedis->del_lock($signKey);
        }
        
        $MyRedis->del_lock($lockKey);
        
        $is_two = 0;
        $pay_data = [];
        $tmp = [];
        $tmp[] = [
            'num' => $usdt_num,
            'collection_address' => $collection_address,
        ];
        $pay_data[] = [
            'total' => $usdt_num,
            'contract_address' => $usdtCurrency->contract_address,
            'list' => $tmp
        ];
        
        $data = [
            'remarks' => $ordernum,
            'is_chain' => $is_chain,
            'is_two' => $is_two,
            'pay_data' => $pay_data
        ];
        return responseJson($data);
    }
    
    public function signLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = SignOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['usdt_num','sign_power','dogbee','hash','created_at'])
            ->toArray();
//         if ($list) {
//             foreach ($list as &$v) {
//             }
//         }
        return responseJson($list);
    }
    
}
