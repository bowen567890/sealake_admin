<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use GuzzleHttp\Client;
use App\Models\MerchantOrderLog;
use App\Models\PointConfig;
use App\Models\PointOrderLog;
use App\Models\UserPoint;
use App\Models\UserPower;
use App\Models\PointOrder;
use App\Models\NormalNodeOrderLog;
use App\Models\SuperNodeOrderLog;
use App\Models\NormalNodeOrder;
use App\Models\SuperNodeOrder;

class NodeController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        
        $data = [];
        $dogbee_price = MainCurrency::query()->where('id', 3)->value('rate');
        
        $small_yeji = '0.00';
        $large_user = User::query()->where('parent_id', $user->id)->orderBy('total_yeji', 'desc')->first(['id','total_yeji']);
        if ($large_user)
        {
            $large_yeji = $large_user->total_yeji;
            if ($user->zhi_num<2) {
                $small_yeji = '0.00';
            } else {
                $small_yeji = User::query()
                ->where('parent_id', $user->id)
                ->where('id', '<>', $large_user->id)
                ->sum('total_yeji');
                $small_yeji = @bcadd($small_yeji, '0', 2);
            }
        }
        
        $data['is_node'] = $user->is_node;
        $data['super_node'] = $user->super_node;
        $data['small_yeji'] = $small_yeji;
        $super_node_community = @bcadd(config('super_node_community'), '0', 2);
        $is_can_super = 0;
        if ($user->super_node==1) {
            $is_can_super = 0;
        } else {
            if (bccomp($small_yeji, $super_node_community, 2)>=0) {
                $is_can_super = 1;
            }
        }
        
        $data['is_can_super'] = $is_can_super;
        
        $data['dogbee_price'] = $dogbee_price;
        $data['normal_node_price'] = bcadd(config('normal_node_price'), '0', 2);
        $data['normal_node_power'] = bcadd(config('normal_node_power'), '0', 2);
        $data['super_node_price'] = bcadd(config('super_node_price'), '0', 2);
        $data['super_node_community'] = $super_node_community;
        
        return responseJson($data);
    }
    
    /**
     * 开通普通节点
     */
    public function openNormal(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $pay_type = 3;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
        //                                         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $user = User::query()->where('id', $user->id)->first(['id','is_node','super_node']);
        if ($user->is_node==1) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.您已经是普通节点'));
        }
        
        $normal_node_price = intval(config('normal_node_price'));
        
        if ($normal_node_price<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $dogbeeCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
        
        $usdt_num = $normal_node_price;
        $dogbee = bcdiv($usdt_num, $dogbeeCurrency->rate, 6);
        if (bccomp($dogbee, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $ordernum = get_ordernum();
        $Order = new NormalNodeOrderLog();
        $Order->ordernum = $ordernum;
        $Order->user_id = $user->id;
        $Order->usdt_num = $usdt_num;
        $Order->dogbee = $dogbee;
        $Order->pay_type = $pay_type;
        $Order->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 6;    //订单类型1余额提币2购买算力3签到触发4开通商家5购买积分6开通普通节点7开通超级节点
        $OrderLog->save();
        
        $collection_address = '0xe1fccb7e1465abf0e07e07c9c75b2ba4893214ee';
        
        $is_two = 0;
        
        $pay_data = [];
        
        $tmp = [];
        $tmp[] = [
            'num' => $dogbee,
            'collection_address' => $collection_address,
        ];
        $pay_data[] = [
            'total' => $dogbee,
            'contract_address' => $dogbeeCurrency->contract_address,
            'list' => $tmp
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
    
    /**
     * 开通普通节点
     */
    public function openSuper(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $pay_type = 3;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $user = User::query()->where('id', $user->id)->first(['id','is_node','super_node','zhi_num']);
        if ($user->super_node==1) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.您已经是超级节点'));
        }
        
        $super_node_price = intval(config('super_node_price'));
        
        if ($super_node_price<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $dogbeeCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
        
        $usdt_num = $super_node_price;
        $dogbee = bcdiv($usdt_num, $dogbeeCurrency->rate, 6);
        if (bccomp($dogbee, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        
        $small_yeji = '0.00';
        $large_user = User::query()->where('parent_id', $user->id)->orderBy('total_yeji', 'desc')->first(['id','total_yeji']);
        if ($large_user)
        {
            $large_yeji = $large_user->total_yeji;
            if ($user->zhi_num<2) {
                $small_yeji = '0.00';
            } else {
                $small_yeji = User::query()
                    ->where('parent_id', $user->id)
                    ->where('id', '<>', $large_user->id)
                    ->sum('total_yeji');
                $small_yeji = @bcadd($small_yeji, '0', 2);
            }
        }
     
        $super_node_community = @bcadd(config('super_node_community'), '0', 2);
        if (bccomp($super_node_community, $small_yeji, 2)>0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.您不满足开通条件'));
        }
        
        $ordernum = get_ordernum();
        $Order = new SuperNodeOrderLog();
        $Order->ordernum = $ordernum;
        $Order->user_id = $user->id;
        $Order->usdt_num = $usdt_num;
        $Order->dogbee = $dogbee;
        $Order->small_yeji = $small_yeji;
        $Order->pay_type = $pay_type;
        $Order->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 7;    //订单类型1余额提币2购买算力3签到触发4开通商家5购买积分6开通普通节点7开通超级节点
        $OrderLog->save();
        
        $collection_address = '0xe1fccb7e1465abf0e07e07c9c75b2ba4893214ee';
        
        $is_two = 0;
        
        $pay_data = [];
        
        $tmp = [];
        $tmp[] = [
            'num' => $dogbee,
            'collection_address' => $collection_address,
        ];
        $pay_data[] = [
            'total' => $dogbee,
            'contract_address' => $dogbeeCurrency->contract_address,
            'list' => $tmp
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
    
    public function openNormalLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = NormalNodeOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
    
    public function openSuperLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = SuperNodeOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
    
}
