<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\MainCurrency;
use App\Models\LuckyPool;
use App\Models\UserSpacex;
use App\Models\LuckyLog;
use App\Models\UserDogbee;

class LuckyController extends Controller
{
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function index(Request $request)
    {
        $in = $request->input();
        $user = auth()->user();
        $LuckyPool = LuckyPool::query()->where('id', 1)->first();
        
        $data['pool'] = $LuckyPool->pool;
        $data['random_min'] = $LuckyPool->random_min;
        $data['random_max'] = $LuckyPool->random_max;
        $data['lottery_num'] = $user->lottery_num;
        
        return responseJson($data);
    }
    
    /**
     */
    public function draw(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        if ($user->status==0){
            auth()->logout();
            return responseValidateError(__('error.用户已被禁止登录'));
        }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 20);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $user = User::query()->where('id', $user->id)->first(['id','lottery_num']);
        if ($user->lottery_num<=0) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.抽奖次数不足'));
        }
        
        $drawKey = 'LuckyDraw';
        $MyRedis->del_lock($drawKey);
        $drawLock = $MyRedis->add_lock($drawKey, 15);
        if(!$drawLock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $datetime = date('Y-m-d H:i:s');
        $dogbee = $rate = $subPool = '0';
        $ordernum = get_ordernum();
        $up['lottery_num'] = DB::raw("`lottery_num`-1");
        
        $LuckyPool = LuckyPool::query()->where('id', 1)->first();
        if (bccomp($LuckyPool->pool, '0', 6)>0) 
        {
            $random_min = bcmul($LuckyPool->random_min, '10000', 0);
            $random_max = bcmul($LuckyPool->random_max, '10000', 0);
            $rate = mt_rand($random_min, $random_max);
            if ($rate>0) {
                $rate = bcdiv($rate, '10000', 4);
            }
            $dogbee = bcmul($LuckyPool->pool, $rate, 6);
            if (bccomp($dogbee, '0', 6)>0) 
            {
                $subPool = bccomp($LuckyPool->pool, $dogbee, 6)>=0 ? $dogbee : $LuckyPool->pool;
                LuckyPool::query()->where('id', 1)->update([
                    'pool' => DB::raw("`pool`-{$dogbee}")
                ]);
                
                $up['dogbee'] = DB::raw("`dogbee`+{$dogbee}");
              
                //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速9幸运抽奖
                $dogbeeData[] = [
                    'ordernum' => $ordernum,
                    'user_id' => $user->id,
                    'from_user_id' => 0,
                    'type' => 1,
                    'cate' => 9,
                    'total' => $dogbee,
                    'msg' => '抽奖获得',
                    'content' => "抽奖获得",
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ];
                UserDogbee::query()->insert($dogbeeData);
            }
        }
        $MyRedis->del_lock($drawKey);
        
        User::query()->where('id', $user->id)->update($up);
        
        $LuckyLog = new LuckyLog();
        $LuckyLog->user_id = $user->id;
        $LuckyLog->rate = $rate;
        $LuckyLog->num = $dogbee;
        $LuckyLog->ordernum = $ordernum;
        $LuckyLog->save();
       
        $MyRedis->del_lock($lockKey);
        return responseJson();
    }
    
    public function drawLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = LuckyLog::query()
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['num','created_at'])
            ->toArray();
        //         if ($list) {
        //             foreach ($list as &$v) {
        //             }
        //         }
        return responseJson($list);
    }
   
}
