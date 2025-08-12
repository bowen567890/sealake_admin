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
use App\Models\NodeConfig;
use App\Models\TicketConfig;
use App\Models\RankConfig;
use App\Models\NodeOrderLog;
use App\Models\NodeOrder;
use App\Models\TicketOrderLog;

class TicketController extends Controller
{
    public function config(Request $request)
    {
        $user = auth()->user();
        $list = TicketConfig::GetListCache();
        return responseJson($list);
    }
    
    /**
     * 购买入场券
     */
    public function buy(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if (!isset($in['id']) || intval($in['id'])<=0) {
            return responseValidateError(__('error.请选择入场券'));
        }
        
        $pay_type = 1;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
                                                $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $usdtCurrency = MainCurrency::query()->where('id', 1)->first(['rate','contract_address']);
        
        $TicketConfig = TicketConfig::query()->where('lv', $lv)->first();
        if (!$TicketConfig || $TicketConfig->ticket_price<=0 || $TicketConfig->status!=1) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $ticket_price = $TicketConfig->ticket_price;
        
        $ordernum = get_ordernum();
        $Order = new TicketOrderLog();
        $Order->ordernum = $ordernum;
        $Order->user_id = $user->id;
        $Order->lv = $NodeConfig->lv;
        $Order->price = $NodeConfig->price;
        $Order->gift_ticket_id = $NodeConfig->gift_ticket_id;
        $Order->gift_ticket_num = $NodeConfig->gift_ticket_num;
        $Order->gift_rank_id = $NodeConfig->gift_rank_id;
        $Order->static_rate = $NodeConfig->static_rate;
        $Order->pay_type = $pay_type;
        $Order->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 2;    //订单类型1余额提币2购买节点3购买入场券4缴纳保证金
        $OrderLog->save();
        
        $is_two = 0;
        
        $pay_data = [];
        
        $tmp = [];
        $tmp[] = [
            'num' => $price,
        ];
        $pay_data[] = [
            'total' => $price,
            'contract_address' => $usdtCurrency->contract_address,
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
    
   
    
    public function openNodeLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = NodeOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
}
