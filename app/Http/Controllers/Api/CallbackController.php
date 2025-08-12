<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MyRedis;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\Withdraw;
use App\Models\Recharge;
use App\Models\MainCurrency;
use App\Models\Config;
use App\Models\UserUsdt;
use App\Models\RankConfig;
use App\Models\LockRecord;
use App\Models\FeeOrder;
use App\Models\WithdrawFeeOrder;
use App\Models\LuckyPool;
use App\Models\PowerOrderLog;
use App\Models\PowerOrder;
use App\Models\SyncPower;
use App\Models\SignOrderLog;
use App\Models\SignOrder;
use App\Models\NodePool;
use App\Models\MerchantOrderLog;
use App\Models\MerchantOrder;
use App\Models\PointOrderLog;
use App\Models\PointOrder;
use App\Models\EncryptionServiceModel;
use App\Models\NormalNodeOrderLog;
use App\Models\NormalNodeOrder;
use App\Models\SuperNodeOrderLog;
use App\Models\SuperNodeOrder;
use App\Models\NodeOrderLog;
use App\Models\NodeOrder;
use App\Models\TicketConfig;
use App\Models\UserTicket;
use App\Models\NodeConfig;


class CallbackController extends Controller
{
    protected static $encrypter;
    /**
     * 初始化加密服务
     */
    public static function init()
    {
        if (!self::$encrypter) {
            self::$encrypter = app(EncryptionServiceModel::class);
        }
    }
    
    //订单回调
    public function callback(Request $request)
    {
        $in = $request->input();
        $ordernum = isset($in['remarks']) && $in['remarks'] ? $in['remarks'] : '';
        Log::channel('order_callback')->info('收到回调', $in);
        
        $order = OrderLog::query()->where('ordernum', $ordernum)->first();
        if (!$order) {
            return responseValidateError('订单不存在');
        }
        $order->content = json_encode($in);
        $order->save();
        
        //订单类型1余额提币2购买节点3购买入场券4缴纳保证金
        if ($order->type==1){
            $this->withdraw($in);
        } else if ($order->type==2) {
            $this->buyNode($in);
        } else if ($order->type==3) {
            $this->signPower($in);
        } else if ($order->type==4) {
            $this->openMerchant($in);
        }
        
        return responseValidateError('订单未找到');
    }
    //购买算力
    private function buyPower($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:buyPower:'.$ordernum;
        $MyRedis = new MyRedis();
//                 $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        //         Log::channel('contribution_order')->info('上锁失败', $in);
        if(!$ret){
            Log::channel('buy_power')->info('上锁失败', $in);
            die;
        }
        $order = PowerOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('buy_power')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        //支付类型1USDT(链上)
        if ($order->pay_type==1)
        {
            if (!isset($in['coin_token']) || $in['coin_token']!='USDT')
            {
                Log::channel('buy_power')->info('币种不正确', $in);
                $MyRedis->del_lock($lockKey);
                die;
            }
        }
        else
        {
            Log::channel('buy_power')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
      
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 6);
        $amount1 = @bcadd($in['amount1'], '0', 6);
        //{"amount":"10","amount1":"30","block_hash":"0xd7077e1e12a98daae1c65455e28d7b4d9d5435cd35a4055028621968849c612b","coin_token":"USDT","coin_token1":"USDT","contract_address":"0x55d398326f99059ff775485246999027b3197955","contract_address1":"0x55d398326f99059ff775485246999027b3197955","customeAmount":null,"customeCoin":null,"customeUser":null,"from_address":"0xad9a6888fd00f7b48fbdc893b4b8d62bb3561488","hash":"0xddd726326b65e51ec3e813116ca722e9af5c68db1909a8b5143a5b006e10312c","imputation_hash":null,"main_chain":"bsc","recharge_type":"1","remarks":"25050910455429282591","status":"3","to_address":"0xad9a6888fd00f7b48fbdc893b4b8d62bb3561488","token_id":null} 
        $buy_map = $order->buy_map ? json_decode($order->buy_map, true) : [];
        if ($buy_map && isset($buy_map[0]) && isset($buy_map[1])) 
        {
            if (bccomp($buy_map[0]['num'], $amount, 6)>0 || bccomp($buy_map[1]['num'], $amount1, 6)>0)
            {
                Log::channel('buy_power')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
                $MyRedis->del_lock($lockKey);
                die;
            }
        }
     
       
        if ($in['status']==3 && $order->pay_status==0)
        {
            $time = time();
            $date = date('Y-m-d H:i:s', $time);
            $order->pay_status = 1;
            $order->hash = $hash;
            $order->finish_time = $date;
            $order->save();
//             $user = User::query()->where('id', $order->user_id)->first();
            
            $PowerOrder = new PowerOrder();
            $PowerOrder->user_id = $order->user_id;
            $PowerOrder->ordernum = $order->ordernum;
            $PowerOrder->power = $order->power;
            $PowerOrder->usdt_num = $order->usdt_num;
            $PowerOrder->pay_type = $order->pay_type;
            $PowerOrder->hash = $hash;
            $PowerOrder->save();
            
            $SyncPower = new SyncPower();
            $SyncPower->user_id = $PowerOrder->user_id;
            $SyncPower->order_id = $PowerOrder->id;
            $SyncPower->type = 1;  //类型1购买算力2算力签到
            $SyncPower->usdt = $PowerOrder->usdt_num;
            $SyncPower->power = $order->power;
            $SyncPower->ordernum = $order->ordernum;
            $SyncPower->save();
                
            //节点池
            $NodePool = NodePool::query()->where('id', 1)->first();
            if ($NodePool) 
            {
                //普通节点池
                if (bccomp($NodePool->w_rate, '0', 4)>0) {
                    $poolNum = bcmul($order->usdt_num, $NodePool->w_rate, 6);
                    if (bccomp($poolNum, '0', 6)>0) {
                        NodePool::query()->where('id', 1)->increment('pool', $poolNum);
                    }
                }
                //超级节点池
                if (bccomp($NodePool->super_w_rate, '0', 4)>0) {
                    $poolNum = bcmul($order->usdt_num, $NodePool->super_w_rate, 6);
                    if (bccomp($poolNum, '0', 6)>0) {
                        NodePool::query()->where('id', 1)->increment('super_pool', $poolNum);
                    }
                }
            }
            
            //开通普通节点方式2,一次算力报单支付大于等于此数USDT
            $normal_node_power = intval(config('normal_node_power'));
            if (bccomp($PowerOrder->usdt_num, $normal_node_power, 2)>=0) 
            {
                $user = User::query()->where('id', $PowerOrder->user_id)->first(['id','is_node']);
                if ($user->is_node==0) {
                    $user->is_node = 1;
                    $user->save();
                }
            }
            
            $userModel = new User();
//             //个人业绩
//             $userModel->handleAchievement($user->id, $order->total);
//             $userModel->handlePerformance($user->path, $order->total);
            
            $this->setOrderStatus($ordernum, 1);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    //算力签到
    private function signPower($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:signPower:'.$ordernum;
        $MyRedis = new MyRedis();
//                                 $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('sign_power')->info('上锁失败', $in);
            die;
        }
        
        $order = SignOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('sign_power')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='USDT')
        {
            Log::channel('sign_power')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 6);
        
        //支付类型1USDT(链上)
        if (bccomp($order->usdt_num, $amount, 6)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('sign_power')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            $signKey = 'signPowerBack:'.$order->user_id;
//                                                 $MyRedis->del_lock($signKey);
            $retRes = $MyRedis->setnx_lock($signKey, 30);
            if(!$retRes){
                Log::channel('sign_power')->info('加速上锁失败', $in);
                die;
            }
            
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            
            $order->pay_status = 1;
            $order->hash = $hash;
            $order->finish_time = $datetime;
            $order->save();

            //生成订单记录
            $SignOrder = new SignOrder();
            $SignOrder->ordernum = $order->ordernum;
            $SignOrder->user_id = $order->user_id;
            $SignOrder->usdt_num = $order->usdt_num;
            $SignOrder->sign_power = $order->sign_power;
            $SignOrder->pay_type = $order->pay_type; 
            $SignOrder->hash = $hash;
            $SignOrder->save();
            
            $is_repeat = 0;
            
            $user = User::query()->where('id', $order->user_id)->first(['id','last_sign_time']);
            
            if ($user->last_sign_time) 
            {
                $sign_interval_day = intval(config('sign_interval_day'));
                $sign_interval_day = $sign_interval_day>0 ? $sign_interval_day : 7;
                $last_sign_time = strtotime($user->last_sign_time);
                $next_sign_time = $last_sign_time+86400*$sign_interval_day;
                if ($next_sign_time>=$time) {
                    $is_repeat = 1;
                }
            }
            
            if (!$is_repeat)
            {
                $user->last_sign_time = $datetime;
                $user->save();
                
                $SyncPower = new SyncPower();
                $SyncPower->user_id = $order->user_id;
                $SyncPower->order_id = $SignOrder->id;
                $SyncPower->type = 2;  //类型1购买算力2算力签到
                $SyncPower->usdt = $order->usdt_num;
                $SyncPower->power = $order->sign_power;
                $SyncPower->ordernum = $order->ordernum;
                $SyncPower->save();
            } else {
                $SignOrder->is_repeat = 1;
                $SignOrder->save();
            }
            
            $MyRedis->del_lock($signKey);
            
            $this->setOrderStatus($ordernum, 1);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    //开通商家
    private function buyNode($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:buyNode:'.$ordernum;
        $MyRedis = new MyRedis();
                                        $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('buy_node')->info('上锁失败', $in);
            die;
        }
        
        $order = NodeOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('buy_node')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='USDT')
        {
            Log::channel('buy_node')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 2);
        
        //支付类型1USDT(链上)
        if (bccomp($order->price, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('buy_node')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            
            DB::beginTransaction();
            try
            {   
                $time = time();
                $datetime = date('Y-m-d H:i:s', $time);
                
                $order->pay_status = 1;
                $order->hash = $hash;
                $order->finish_time = $datetime;
                $order->save();
                
                $NodeOrder = new NodeOrder();
                $NodeOrder->ordernum = $order->ordernum;
                $NodeOrder->user_id = $order->user_id;
                $NodeOrder->lv = $order->lv;
                $NodeOrder->price = $order->price;
                $NodeOrder->gift_ticket_id = $order->gift_ticket_id;
                $NodeOrder->gift_ticket_num = $order->gift_ticket_num;
                $NodeOrder->gift_rank_id = $order->gift_rank_id;
                $NodeOrder->static_rate = $order->static_rate;
                $NodeOrder->pay_type = $order->pay_type;
                $NodeOrder->hash = $hash;
                $NodeOrder->save();
                
                $TicketConfig = TicketConfig::query()->where('id', $order->gift_ticket_id)->first();
                if ($TicketConfig && $order->gift_ticket_num>0)
                {
                    $TicketData = [];
                    for ($i=1; $i<=$order->gift_ticket_num; $i++)
                    {
                        $TicketData[] = [
                            'user_id' => $order->user_id,
                            'ticket_id' => $order->gift_ticket_id,
                            'source_type' => 2, //来源1平台购买2平台赠送3用户赠送
                            'ordernum' => $order->ordernum,
                            'created_at' => $datetime,
                            'updated_at' => $datetime
                        ];
                    }
                    UserTicket::query()->insert($TicketData);
                }
                
                $uup = [];
                $uup['node_rank'] = $order->lv;
                $RankConfig = RankConfig::query()->where('lv', $order->gift_rank_id)->first();
                if ($RankConfig) {
                    $uup['rank'] = $RankConfig->lv;
                    $uup['hold_rank'] = 1;
                }
                if (bccomp($order->static_rate, '0', 2)>0) {
                    $uup['static_rate'] = $order->static_rate;
                }
                User::query()->where('id', $order->user_id)->update($uup);
                
                NodeConfig::query()->where('lv', $order->lv)->update([
                    'stock'=> DB::raw("`stock`-1"),
                    'sales'=> DB::raw("`sales`+1")
                ]);
                
                $this->setOrderStatus($ordernum, 1);
                
                DB::commit();
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                Log::channel('buy_node')->info('回调失败', $in);
                
//                 var_dump($e->getMessage().$e->getLine());die;
            }
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    //开通普通节点
    private function openNormalNode($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:openNormalNode:'.$ordernum;
        $MyRedis = new MyRedis();
        //                                         $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('open_normal_node')->info('上锁失败', $in);
            die;
        }
        
        $order = NormalNodeOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('open_normal_node')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='DOGBEE')
        {
            Log::channel('open_normal_node')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 2);
        
        //支付类型1USDT(链上)
        if (bccomp($order->dogbee, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('open_normal_node')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            
            $order->pay_status = 1;
            $order->hash = $hash;
            $order->finish_time = $datetime;
            $order->save();
            
            $newOrder = new NormalNodeOrder();
            $newOrder->ordernum = $order->ordernum;
            $newOrder->user_id = $order->user_id;
            $newOrder->usdt_num = $order->usdt_num;
            $newOrder->dogbee = $order->dogbee;
            $newOrder->pay_type = $order->pay_type;
            $newOrder->hash = $hash;
            $newOrder->finish_time = $datetime;
            $newOrder->save();
            
            User::query()->where('id', $order->user_id)->update(['is_node'=>1]);
            
            $this->setOrderStatus($ordernum, 1);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    //开通超级节点
    private function openSuperNode($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:openSuperNode:'.$ordernum;
        $MyRedis = new MyRedis();
        //                                         $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('open_super_node')->info('上锁失败', $in);
            die;
        }
        
        $order = SuperNodeOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('open_super_node')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='DOGBEE')
        {
            Log::channel('open_super_node')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 2);
        
        //支付类型1USDT(链上)
        if (bccomp($order->dogbee, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('open_super_node')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            
            $order->pay_status = 1;
            $order->hash = $hash;
            $order->finish_time = $datetime;
            $order->save();
            
            $newOrder = new SuperNodeOrder();
            $newOrder->ordernum = $order->ordernum;
            $newOrder->user_id = $order->user_id;
            $newOrder->usdt_num = $order->usdt_num;
            $newOrder->dogbee = $order->dogbee;
            $newOrder->small_yeji = $order->small_yeji;
            $newOrder->pay_type = $order->pay_type;
            $newOrder->hash = $hash;
            $newOrder->finish_time = $datetime;
            $newOrder->save();
            
            User::query()->where('id', $order->user_id)->update(['super_node'=>1]);
            
            $this->setOrderStatus($ordernum, 1);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    
    
    //购买积分
    private function buyPoint($in)
    {
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:buyPoint:'.$ordernum;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $ret = $MyRedis->setnx_lock($lockKey, 30);
        if(!$ret){
            Log::channel('buy_point')->info('上锁失败', $in);
            die;
        }
        
        $order = PointOrderLog::query()->where(['ordernum'=>$ordernum, 'pay_status'=>0])->first();
        if (!$order) {
            Log::channel('buy_point')->info('订单不存在', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if (!isset($in['coin_token']) || $in['coin_token']!='DOGBEE')
        {
            Log::channel('buy_point')->info('币种不正确', $in);
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
        $amount = @bcadd($in['amount'], '0', 2);
        
        //支付类型1USDT(链上)
        if (bccomp($order->dogbee, $amount, 2)>0) {
            if ($in['status']==3 && $order->pay_status==0) {
                Log::channel('buy_point')->info('金额有误', $in);
                $order->pay_status = 2;
                $order->hash = $hash;
                $order->save();
                $this->setOrderStatus($ordernum, 2);
            }
            $MyRedis->del_lock($lockKey);
            die;
        }
        
        if ($in['status']==3 && $order->pay_status==0)
        {
            $time = time();
            $datetime = date('Y-m-d H:i:s', $time);
            
            $order->pay_status = 1;
            $order->hash = $hash;
            $order->finish_time = $datetime;
            $order->save();
            
            $PointOrder = new PointOrder();
            $PointOrder->ordernum = $order->ordernum;
            $PointOrder->user_id = $order->user_id;
            $PointOrder->point = $order->point;
            $PointOrder->usdt_num = $order->usdt_num;
            $PointOrder->dogbee = $order->dogbee;
            $PointOrder->pay_type = $order->pay_type;
            $PointOrder->hash = $hash;
            $PointOrder->finish_time = $datetime;
            $PointOrder->save();
            
            $userModel = new User();
            //分类1系统增加2系统扣除3开通商家4购买积分5转出扣除6转入获得7兑换算力
            $userModel->handleUser('point', $order->user_id, $order->point, 1, ['cate'=>4, 'msg'=>'购买积分', 'ordernum'=>$order->ordernum]);
            
            $this->setOrderStatus($ordernum, 1);
        }
        $MyRedis->del_lock($lockKey);
        exit('success');
    }
    

    
    
    private function withdraw($in)
    {
        $data = $in;
        $ordernum = $in['remarks'];
        
        $lockKey = 'callback:withdraw:'.$in['remarks'];
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            Log::channel('withdraw_callback')->info('回调上锁失败', $in);
            echo '上锁失败';
            die;
        }
        
        $withdraw = Withdraw::query()
            ->where('ordernum', $in['remarks'])
//             ->where('fee_status', 1)
            ->first();
        if (!$withdraw){
            Log::channel('withdraw_callback')->info('未找到数据无法继续');
            $MyRedis->del_lock($lockKey);
            exit();
        }
        if ($withdraw->status!=0){
            Log::channel('withdraw_callback')->info('数据已被处理，无需继续处理');
            $MyRedis->del_lock($lockKey);
            exit();
        }
        
        $userModel = new User();
        DB::beginTransaction();
        try
        {
            $hash = isset($in['hash']) && $in['hash'] ? $in['hash'] : '';
            if ($data['status']==5)
            {
                $withdraw->status = 1;
                $withdraw->finsh_time = date('Y-m-d H:i:s');
                $withdraw->hash = $hash;
                $withdraw->save();
                
                if ($withdraw->coin_type==3 && bccomp($withdraw->pool_num, '0', 6)>0) {
                    LuckyPool::query()->where('id', 1)->increment('pool', $withdraw->pool_num);
                }
                
                $this->setOrderStatus($ordernum, 1);
                DB::commit();
                $MyRedis->del_lock($lockKey);
            }
            else if ($data['status']==6)
            {
                if ($withdraw->coin_type==1) {
                    $table = 'usdt';
                } else {
                    $table = 'dogbee';
                }
                
                $userModel->handleUser($table, $withdraw->user_id, $withdraw->num, 1, ['cate'=>4, 'msg'=>'提币驳回', 'ordernum'=>$withdraw->ordernum]);
                
                if (bccomp($withdraw->fee_power, '0', 6)>0) {
                    //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速9积分兑换10提币扣除11提币驳回
                    $userModel->handleUser('power', $withdraw->user_id, $withdraw->fee_power, 1, ['cate'=>11, 'msg'=>'提币驳回', 'ordernum'=>$withdraw->ordernum]);
                }
                
                $withdraw->status = 2;
                $withdraw->finsh_time = date('Y-m-d H:i:s');
                $withdraw->hash = $hash;
                $withdraw->save();
                
                $this->setOrderStatus($ordernum, 1);
                DB::commit();
                $MyRedis->del_lock($lockKey);
            }
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $MyRedis->del_lock($lockKey);
            Log::channel('withdraw_callback')->info('提币回调异常');
        }
        
        $MyRedis->del_lock($lockKey);
        echo '提币成功';
        die;
    }

    /**
     * 修改订单状态
     */
    protected function setOrderStatus($ordernum, $status=1) {
        OrderLog::query()->where('ordernum', $ordernum)->where('ordernum', $ordernum)->update(['status'=>$status]);
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function searchPrice(Request $request)
    {
        
        
//         $a = '0.09335684145031986';
//         $b = '0.001234744507092474';
        
//         $price = bcdiv($a, $b, 6);
//         var_dump($price);die;
//         try
//         {
//             $coinId = 'bitcoin';
//             $json = file_get_contents("https://api.coincap.io/v2/assets/{$coinId}");
//             if (is_string($json) && $json)
//             {
//                 $newData = [];
//                 $jsonArr = json_decode($json, true);
//                 if (is_array($jsonArr) && $jsonArr && isset($jsonArr['data']) && is_array($jsonArr['data']) && $jsonArr['data'] && isset($jsonArr['data']['priceUsd']))
//                 {
                    
//                     var_dump($jsonArr['data']['priceUsd']);die;
//                 }
//             }
//         }
//         catch (\Exception $e)
//         {
//             Log::channel('token_price')->info('获取价格失败:'.$e->getMessage().$e->getLine());
//         }
        
//     $MyRedis = new MyRedis();
//     $list = User::query()->get(['id'])->toArray();
//     foreach ($list as $val) {
//         $lastKey = 'last_token:'.$val['id'];
//         $MyRedis->del_lock($lastKey);
//     }
//         var_dump(888888);die;
        



//方法1
//https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token=0x55d398326f99059ff775485246999027b3197955&to_token=0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657&chain=bsc&max_hops=3&max_routes=6&protocol=v3
    
    $client = new Client();
//     $response = $client->get('https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token=0x55d398326f99059ff775485246999027b3197955&to_token=0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657&chain=bsc&max_hops=3&max_routes=6&protocol=v3');
//     var_dump($response->getBody()->getContents());die;

    $usdt = '11515443.313273482';
    $bnb1 = '21111.028221891538';
    
    $bnb2 = '0.031607639676630286';
    $vv = '34179387.1896077';
    
    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
//     string(14) "545.4705091688"
//     string(13) "17.2410353080"
//     string(12) "0.0000005044"
//     var_dump($bnbUsdtPrice,$usdtNum,$vvPrice);

    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
    
    
    //方法2通过
    $usdt = '11528569768479880436806829';
    $bnb1 = '21088073398281325679560';
    
    $bnb2 = '1234744507092474';
    $vv = '93356841450319864';
    
    $bnbUsdtPrice = bcdiv($usdt, $bnb1, 10);
    $usdtNum = bcmul($bnb2, $bnbUsdtPrice, 10);
    $vvPrice = bcdiv($usdtNum, $vv, 10);
    
    
    
        $in = $request->input();
        $token3 = '';
        $token1 = $in['token1'];
        $token2 = $in['token2'];
        if (isset($in['token3'])) {
            $token3 = $in['token3'];
        }
        
        try
        {
            $queryData['token1'] = $token1;
            $queryData['token2'] = $token2;
            $path[] = $queryData['token1'];
            $path[] = $queryData['token2'];
            if ($token3) {
                $queryData['token3'] = $token3;
                $path[] = $queryData['token3'];
            }
            $queryData['token1Decimals'] = 18;
            $queryData['token2Decimals'] = 18;
            $queryData['token3Decimals'] = 18;
            $token1Decimals = $queryData['token1Decimals'];
            $token2Decimals = $queryData['token2Decimals'];
            $token3Decimals = $queryData['token3Decimals'];
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/getCoinPrice',[
                'json' => [
                    'route_address' => '0x10ed43c718714eb63d5aa57b78b54704e256024e',    //固定不变
                    'amount_in_decimals' => $token1Decimals,
                    'path' => $path
                ]
            ]);
          
            $result = json_decode($response->getBody()->getContents(),true);
            $result['path'] = $path;
            $result['amount_in_decimals'] = $token1Decimals;
            
            echo json_encode($result);die;
            $price =  empty($result['data']) ? 0 : number_format($result['data'][count($result['data'])-1], $token2Decimals, '.', '');
            $price =  sprintf('%.10f',$price/pow(10,$token2Decimals));
            var_dump($price);die;
        }catch (\Exception $e){
            var_dump($e->getMessage().$e->getLine());
        }
    }
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function lpInfo(Request $request)
    {
        $usdtContractAddress = env('USDT_ADDRESS');
        $busdContractAddress = env('BUSD_ADDRESS');
        
        try
        {
            $in = $request->input();
            $contract_address = trim($in['contract_address']);
            $contract_address = strtolower($contract_address);
            
//             $currency = MainCurrency::query()
//                 ->where('name', '=', 'IDO-LP')
//                 ->first(['name','contract_address','precision']);
//             $contract_address = $currency->contract_address;
            
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            /*
             $result = [
             'code' => 200,
             'data' => [
             'reserve0' => 1600000000000000000,
             'reserve1' => 6257063425359314877,
             'totalSupply' => 3162277660168379331,
             ],
             ];
             */
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']))
            {
                Log::channel('lp_info')->info('查询LP信息失败');
            }
            else
            {
                var_dump($result);die;
                
                
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                if ($token1==$usdtContractAddress || $token1==$busdContractAddress) {
                    $coin_price = @bcdiv($result['data']['reserve1'], $result['data']['reserve0'], 10);
                } else {
                    $coin_price = @bcdiv($result['data']['reserve0'], $result['data']['reserve1'], 10);
                }
                
                var_dump($coin_price, $result);die;
            }
            
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询LP信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    /**
     * 查询代币价格 代币1=>代币2 价格
     * @param: $token1           代币1
     * @param: $token2           代币2
     * @param: $token1Decimals   精度
     * @param: $token2Decimals
     */
    public function lpInfov3(Request $request)
    {
        
        try
        {
            $in = $request->input();
            $contract_address = trim($in['contract_address']);
            $contract_address = strtolower($contract_address);
            
            $is_fan = intval($in['is_fan']);
            
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lp3Info',[
                'form_params' => [
                    'contract_address' => $contract_address,
                    'is_fan' => $is_fan
                ]
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            var_dump($is_fan,$contract_address,$result);die;
            /*
             $result = [
                  'code' => 200,
                  'data' => [
                        'token0' => '0x7130d2A12B9BCbFAe4f2634d864A1Ee1Ce3Ead9c',
                        'token0Fee' => '57589887794532494',
                        'token1' => env('BUSD_ADDRESS'),
                        'token1Fee' => '1675178344085188180815',
                      ],
                  'msg' => 'success'
                ];
             */
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['token0Fee']) || !isset($result['data']['token1Fee']))
            {
                Log::channel('lp_info')->info('查询LP信息失败');
            }
            else
            {
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                if ($token1==$usdtContractAddress) {
                    $coin_price = @bcdiv($result['data']['token1Fee'], $result['data']['token0Fee'], 10);
                } else {
                    $coin_price = @bcdiv($result['data']['token0Fee'], $result['data']['token1Fee'], 10);
                }
                var_dump($coin_price, $result,$result['data']['token0Fee'],$result['data']['token1Fee']);die;
            }
            
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询LP信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    
    
    
    /**
     * 新版本查询自动买币结果
     */
    public function getTransactionDetail(Request $request)
    {
        try
        {
            $in = $request->input();
            $ordernum = isset($in['ordernum']) && $in['ordernum'] ? $in['ordernum'] : '';
            if (!$ordernum) {
                echo 22222;die;
            }
            
            $client = new Client();
            $response = $client->post('127.0.0.1:9099/getTransactionDetail',[
                'form_params' => [
                    'contract_address' => env('RECHARGE_CONTRACT_ADDRESS'),   //查询自动买币的充值合约地址
                    'order_no' => $ordernum,
                ],
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['out_num']))
            {
                Log::channel('auto_trade_detail')->info('查询自动买币信息失败', $result);
            }
            else
            {
                $pows = pow(10,18);
                $amount = @bcadd($result['data']['out_num'], '0', 6);
                $amount = bcdiv($amount, $pows, 6);    //钱包系统返回来要除以18位
            }
            var_dump($amount,$result);die;
            echo json_encode($result);die;
        }
        catch (\Exception $e)
        {
            Log::channel('auto_trade_detail')->info('查询自动买币信息失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
    }
    
    /**
     * 释放分红提币
     */
    public function reptile()
    {
        require_once(__DIR__.'/Snoopy.class.php');
        
        $contractAddress = '0x346827CdaA4947f89cB1009cDC4d9473FBc8Bdaa';
        $snoopy = new Snoopy;
        $page = 1;  
        $url = "https://longswap.app/swap?outputCurrency0x0ef507df23ebb72b2fecbe722dfbc5d0e023f657";
//         $url = "https://bscscan.com/token/generic-tokenholders2?a={$contractAddress}&sid=c36547018faca9a52888b6b654edb21a&m=normal&s=6198121588559079236743484&p={$page}";
        $data = $this->getData($snoopy, $url, true);
        $list = $data['list'];
        if($data['totalPage']>0 && $data['totalPage']>$page && $page!=0)
        {
            $diffNum = $data['totalPage']>=10 ? 10 : $data['totalPage'];
            for ($i=2; $i<=$diffNum; $i++) {
                $pp = $i;
//                 $url = "https://bscscan.com/token/generic-tokentxns2?contractAddress={$contractAddress}&mode=&sid=c36547018faca9a52888b6b654edb21a&m=normal&p={$pp}";
                $url = "https://bscscan.com/token/generic-tokenholders2?a={$contractAddress}&sid=c36547018faca9a52888b6b654edb21a&m=normal&s=6198121588559079236743484&p={$pp}";
                $tmp = $this->getData($snoopy, $url, true);
                $list = array_merge($list, $tmp['list']);
                usleep(100000);//等待100ms
            }
        }
        
        if ($list) {
            $newData = [];
            foreach ($list as $val) {
               
            }
        }
        
        echo json_encode($newData);die;
    }
    
    public function getData($snoopy, $url, $getPage=false)
    {
        $arr['totalPage'] = 0;
        $arr['list'] = [];
        
        $snoopy->agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36";
        $snoopy->referer = "https://bscscan.com/token/generic-tokentxns2?m=normal&contractAddress=0x210e2b878c8e06a4ca52a9d0e93942bfc5950b95&a=&sid=c36547018faca9a52888b6b654edb21a&p=1";
        $snoopy->cookies["ASP.NET_SessionId"] = 'zuxl5bi1vcrmm0dpnjohuasi';
        $snoopy->fetch($url);
        $html = $snoopy->results;
        if($html)
        {
            if ($getPage)
            {
                //总页数
                preg_match('/<li Class="page-item disabled"[^>]*?>(.*?)<\/li>/s', $html, $li);
                if (is_array($li) && isset($li[1]) && $li[1])
                {
                    preg_match_all("/<strong[^>]*?>(.*?)<\/strong>/s", $li[1], $strong);
                    if (is_array($strong) && isset($strong[1]) && is_array($strong[1]) && $strong[1])
                    {
                        if (isset($strong[1][1])) {
                            $arr['totalPage'] = intval($strong[1][1]);
                        }
                    }
                }
            }
            
            //匹配数据
            preg_match("/<tbody[^>]*?>(.*?)<\/tbody>/s", $html, $tbody);
            if (is_array($tbody) && isset($tbody[1]) && $tbody[1])
            {
                $data = [];
                preg_match_all("/<tr[^>]*?>(.*?)<\/tr>/s", $tbody[1], $tr);
                if (is_array($tr) && isset($tr[1]) && is_array($tr[1]) && $tr[1])
                {
                    foreach ($tr[1] as $t)
                    {
                        $tr = [];
                        preg_match_all("/<td[^>]*?>(.*?)<\/td>/s", $t, $td);
                        if (is_array($td) && isset($td[0]) && $td[0])
                        {
                            foreach ($td[0] as $dk=>$d)
                            {
                                $field = strip_tags($d);
                                $field = trim($field);
                                if ($dk==1) {
                                    $tr['address'] = $field;
                                }
                                if ($dk==2) {
                                    $tr['quantity'] = str_replace(",", "", $field);
                                }
                            }
                            $arr['list'][] = $tr;
                        }
                    }
                }
            }
        }
        return $arr;
    }
    
    public function getChainBalance(Request $request)
    {
    
        $in = $request->input();
        $address = $in['address'];
        $contract_address = $in['contract_address'];
        $userModel = new User();
        //查询地址余额
        $balance = $userModel->GetChainBalance($address, $contract_address);
        var_dump($balance);die;
    }
    
    public function getSpacexPrice1(Request $request)
    {
        $in = $request->input();
        
        $price = '0';
        try
        {
            $wbnbContractAddress = env('WBNB_ADDRESS');
            $contract_address = env('SPACEX_ADDRESS_LP');   //SPACEX|WBNB LP合约地址
            $client = new Client();
            $response = $client->post('http://127.0.0.1:9090/v1/bnb/lpInfo',[
                'form_params' => [
                    'contract_address' => $contract_address //LP合约地址
                ],
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['code']) || $result['code']!=200 ||
                !isset($result['data']) || !isset($result['data']['reserve0']) || !isset($result['data']['reserve1']) ||
                !isset($result['data']['token0']) || !isset($result['data']['token1'])
                )
            {
                Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败');
            }
            else
            {
                $token0 = strtolower($result['data']['token0']);
                $token1 = strtolower($result['data']['token1']);
                
                //查询BNB|USDT 价格
                $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                if (bccomp($bnbUsdtPrice, '0', 10)>0)
                {
                    if ($token1==$wbnbContractAddress)
                    {
                        $usdtNum = bcmul($result['data']['reserve1'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve0'], 10);
                    } else {
                        $usdtNum = bcmul($result['data']['reserve0'], $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $result['data']['reserve1'], 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('lp_info')->info('查询SPACEX-LP信息V2失败', ['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        var_dump($price);
    }
    
    public function getSpacexPrice2(Request $request)
    {
        $price = '0';
        $pair_path = [];
        try
        {
            $bnbAddress = env('WBNB_ADDRESS');
            $spacexAddress = env('SPACEX_ADDRESS');
            $url = "https://api.dryespah.com/v1api/v2/aveswap/getBestRoute_v2?from_token={$bnbAddress}&to_token={$spacexAddress}&chain=bsc&max_hops=3&max_routes=6&protocol=v3";
            
            $client = new Client();
            $response = $client->get($url, [
                'timeout' => 10,
                'verify' => false
            ]);
            
            $result = $response->getBody()->getContents();
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            
            if (!is_array($result) || !$result || !isset($result['status']) || $result['status']!=1 ||
                !isset($result['data']) || !is_array($result['data']) || !$result['data'] ||
                !isset($result['data'][0]) || !is_array($result['data'][0]) || !$result['data'][0] ||
                !isset($result['data'][0]['pair_path']) || !is_array($result['data'][0]['pair_path']) || !$result['data'][0]['pair_path'] ||
                !isset($result['data'][0]['pair_path'][0]) || !is_array($result['data'][0]['pair_path'][0]) || !$result['data'][0]['pair_path'][0] ||
                !isset($result['data'][0]['pair_path'][0]['token_in']) || !isset($result['data'][0]['pair_path'][0]['token_out']) ||
                !isset($result['data'][0]['pair_path'][0]['reserve_in']) || !isset($result['data'][0]['pair_path'][0]['reserve_out']) ||
                !$result['data'][0]['pair_path'][0]['token_in'] || !$result['data'][0]['pair_path'][0]['token_out']
                )
            {
                Log::channel('ave_price')->info('查询SPACEX价格失败');
            }
            else
            {
                $addressArr = [
                    $bnbAddress,
                    $spacexAddress
                ];
                $token_in = strtolower($result['data'][0]['pair_path'][0]['token_in']);
                $token_out = strtolower($result['data'][0]['pair_path'][0]['token_out']);
                if (!in_array($token_in, $addressArr) || !in_array($token_out, $addressArr)) {
                    Log::channel('ave_price')->info('查询SPACEX价格失败');
                }
                else
                {
                    $reserve_in = $result['data'][0]['pair_path'][0]['reserve_in'];
                    $reserve_out = $result['data'][0]['pair_path'][0]['reserve_out'];
                    $bnbUsdtPrice = MainCurrency::query()->where('id', 3)->value('rate');
                    $pair_path = $result['data'][0]['pair_path'][0];
                    if ($token_in==$bnbAddress) {
                        $usdtNum = bcmul($reserve_in, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    } else {
                        $usdtNum = bcmul($reserve_out, $bnbUsdtPrice, 10);
                        $price = bcdiv($usdtNum, $reserve_out, 10);
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            Log::channel('ave_price')->info('查询SPACEX价格失败',['error_msg'=>$e->getMessage().$e->getLine()]);
        }
        
        var_dump($price,$url,$pair_path);
    }
    
    //查询钱包充值最新ID
    public function walletRechargeLastId(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        
        $info = Recharge::on('wallet')->orderBy('id', 'desc')->first(['id']);
        if ($info) {
            $data['id'] = $info->id;
        } else {
            $data['id'] = '暂无数据';
        }
        
        echoJson($data);
    }
    
    //钱包充值本地回调
    public function walletRechargeNotify(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        if (!isset($in['id']) || !$in['id'] || intval($in['id'])<=0) {
            exit('访问失败');
        }
        $id = intval($in['id']);
        $info = Recharge::on('wallet')->where('id', $id)->first();
        if ($info)
        {
            $data = $info->toArray();
            $http = new Client();
            $response = $http->post(env('APP_URL').'/api/callback/callback',[
                'form_params' => $data,
                'timeout' => 10,
                'verify' => false
            ]);
            $result = $response->getBody()->getContents();
        }
        echoJson();
    }
    
    //钱包充值本地批量回调
    public function walletRechargeNotifyPage(Request $request)
    {
        $in = $request->input();
        if (!isset($in['sign']) || !$in['sign'] || $in['sign']!='uxwer2yu6vx') {
            exit('访问失败');
        }
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $list = Recharge::on('wallet')->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        if ($list) {
            $http = new Client();
            foreach ($list as $val) {
                $response = $http->post(env('APP_URL').'/api/callback/callback',[
                    'form_params' => $val,
                    'timeout' => 10,
                    'verify' => false
                ]);
                //                 $result = $response->getBody()->getContents();
            }
            //             Log::channel('recharge_callback')->info('批量充值回调');
        }
        echoJson();
    }
    
    public function encrypterDecrypt(Request $request)
    {
        $in = $request->input();
        $content = $in['content'];
        
        self::init();
        $content = self::$encrypter->decryptData($content);
        echo $content;die;
    }
}
