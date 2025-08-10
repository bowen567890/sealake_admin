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

class MerchantController extends Controller
{
    
    public function index(Request $request)
    {
        $user = auth()->user();
        
        if ($user->is_merchant!=1) {
            return responseValidateError(__('error.您不是商家'));
        }
        $data = [];
        $data['point'] = $user->point;
        $data['point_usdt_rate'] = bcadd(config('point_usdt_rate'), '0', 2);
        $data['usdt_cny_rate'] = bcadd(config('usdt_cny_rate'), '0', 2);
        $data['config_list'] = PointConfig::GetListCache();
        return responseJson($data);
    }
    
    /**
     * 开通商家
     */
    public function open(Request $request)
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
        
        $user = User::query()->where('id', $user->id)->first(['id','is_merchant']);
        if ($user->is_merchant==1) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.您已经是商家'));
        }
        
        $open_merchant_usdt = intval(config('open_merchant_usdt'));
        $open_merchant_point = intval(config('open_merchant_point'));
        
        if ($open_merchant_usdt<=0 || $open_merchant_point<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $dogbeeCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
        
        $usdt_num = $open_merchant_usdt;
        $dogbee = bcdiv($usdt_num, $dogbeeCurrency->rate, 6);
        if (bccomp($dogbee, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $ordernum = get_ordernum();
        $MerchantOrderLog = new MerchantOrderLog();
        $MerchantOrderLog->ordernum = $ordernum;
        $MerchantOrderLog->user_id = $user->id;
        $MerchantOrderLog->point = $open_merchant_point;
        $MerchantOrderLog->usdt_num = $usdt_num;
        $MerchantOrderLog->dogbee = $dogbee;
        $MerchantOrderLog->pay_type = $pay_type;
        $MerchantOrderLog->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 4;    //订单类型1余额提币2购买算力3签到触发4开通商家5购买积分
        $OrderLog->save();
        
        $collection_address = '0x18388A089F4Ef7328985EAb4003c21E4D4eB8ECC';
        
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
     * 购买积分
     */
    public function buyPoint(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        if ($user->is_merchant!=1) {
            return responseValidateError(__('error.您不是商家'));
        }
        
        if (!isset($in['id']) || !$in['id'] || intval($in['id'])<0) {
            return responseValidateError(__('error.请选择购买数量'));
        }
        $id = intval($in['id']);
        
        $pay_type = 3;  //支付类型1USDT(链上)3DOGBEE(链上)
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 30);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $PointConfig = PointConfig::query()
            ->where('id', $id)
            ->where('is_del', 0)
            ->first();
        if (!$PointConfig) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        
        $dogbeeCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
        
        $usdt_num = $PointConfig->usdt_num;
        $dogbee = bcdiv($usdt_num, $dogbeeCurrency->rate, 6);
        if (bccomp($dogbee, '0', 6)<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.系统维护'));
        }
        
        $ordernum = get_ordernum();
        $PointOrderLog = new PointOrderLog();
        $PointOrderLog->ordernum = $ordernum;
        $PointOrderLog->user_id = $user->id;
        $PointOrderLog->point = $PointConfig->point;
        $PointOrderLog->usdt_num = $usdt_num;
        $PointOrderLog->dogbee = $dogbee;
        $PointOrderLog->pay_type = $pay_type;
        $PointOrderLog->save();
        
        $OrderLog = new OrderLog();
        $OrderLog->ordernum = $ordernum;
        $OrderLog->user_id = $user->id;
        $OrderLog->type = 5;    //订单类型1余额提币2购买算力3签到触发4开通商家5购买积分
        $OrderLog->save();
        
        $collection_address = '0x18388A089F4Ef7328985EAb4003c21E4D4eB8ECC';
        
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
    
    
    public function transfer(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        if ($user->is_merchant!=1) {
            return responseValidateError(__('error.您不是商家'));
        }
        
        if (!isset($in['wallet']) || !$in['wallet'])  return responseValidateError(__('error.请输入钱包地址'));
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        
        if (!isset($in['num']) || intval($in['num'])<=0) {
            return responseValidateError(__('error.请输入赠送数量'));
        }
        $num = intval($in['num']);
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 20);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        
        $user = User::query()->where('id', $user->id)->first(['id','point']);
        if (bccomp($user->point, $num, 2)<0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.余额不足'));
        }
        
        //分类1系统增加2系统扣除3开通商家4购买积分5赠送扣除6赠送获得7兑换算力
        $targetUser = User::query()
            ->where('wallet', $wallet)
            ->where('is_del', 0)
            ->first();
        if (!$targetUser) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.赠送用户不存在'));
        }
        
        if ($user->id==$targetUser->id) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.不能赠送给自己'));
        }
        
        User::query()->where('id', $user->id)->decrement('point', $num);
        User::query()->where('id', $targetUser->id)->increment('power', $num);
        
        $datetime = date('Y-m-d H:i:s');
        $ordernum = get_ordernum();
        //分类1系统增加2系统扣除3开通商家4购买积分5赠送扣除6赠送获得7兑换算力
        $pointData = [
            [
                'ordernum' => $ordernum,
                'user_id' => $user->id,
                'from_user_id' => $targetUser->id,
                'type' => 2,
                'cate' => 5,
                'total' => $num,
                'msg' => '赠送扣除',
                'content' => '赠送扣除',
                'created_at' => $datetime,
                'updated_at' => $datetime
            ],
            [
                'ordernum' => $ordernum,
                'user_id' => $targetUser->id,
                'from_user_id' => $user->id,
                'type' => 1,
                'cate' => 6,
                'total' => $num,
                'msg' => '赠送获得',
                'content' => '赠送获得',
                'created_at' => $datetime,
                'updated_at' => $datetime
            ],
            [
                'ordernum' => $ordernum,
                'user_id' => $targetUser->id,
                'from_user_id' => 0,
                'type' => 2,
                'cate' => 7,
                'total' => $num,
                'msg' => '兑换算力',
                'content' => '兑换算力',
                'created_at' => $datetime,
                'updated_at' => $datetime
            ]
        ];
        UserPoint::query()->insert($pointData);
        
        //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速9积分兑换
        $powerData = [
            [
                'ordernum' => $ordernum,
                'user_id' => $targetUser->id,
                'from_user_id' => 0,
                'type' => 1,
                'cate' => 9,
                'total' => $num,
                'msg' => '积分兑换',
                'content' => '积分兑换',
                'created_at' => $datetime,
                'updated_at' => $datetime
            ],
        ];
        UserPower::query()->insert($powerData);
        
        $MyRedis->del_lock($lockKey);
        
        return responseJson();
    }
    
    public function buyPointLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = PointOrder::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get()
            ->toArray();
        return responseJson($list);
    }
    
    public function transferLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        $where['cate'] = 5;
        
        $list = UserPoint::with(['fromuser'])
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['user_id','from_user_id','total','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v)
            {
                $v['total'] = $v['total']*1;
                $v['target_wallet'] = '';
                if ($v['fromuser']) {
                    $v['target_wallet'] = $v['fromuser']['wallet'];
                }
                unset($v['fromuser']);
            }
        }
        return responseJson($list);
    }
    
}
