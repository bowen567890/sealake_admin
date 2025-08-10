<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

use App\Models\MyRedis;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\NodePool;
use App\Models\UserUsdt;

class SyncNodePool extends Command
{
    protected $signature = 'command:SyncNodePool';

    protected $description = '普通节点池子分发';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'command:SyncNodePool';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 1800);
        if ($lock)
        {
            $NodePool = NodePool::query()->where('id', 1)->first();
            if ($NodePool && bccomp($NodePool->pool, '0', 6)>0 && bccomp($NodePool->give_rate, '0', 4)>0)
            {
                $totalNum = bcmul($NodePool->pool, $NodePool->give_rate, 6);
                if (bccomp($totalNum, '0', 6)>0) 
                {
                    $userIds = User::query()
                        ->where('is_node', 1)
                        ->pluck('id')
                        ->toArray();
                    if ($userIds) 
                    {
                        $count = count($userIds);
                        $avgUsdt = bcdiv($totalNum, $count, 6);
                        if (bccomp($avgUsdt, '0', 6)>0) 
                        {
                            $usdtData = [];
                            $ordernum = get_ordernum();
                            $datetime = date('Y-m-d H:i:s');
                            $totalSub = bcmul($avgUsdt, $count, 6);
                            foreach ($userIds as $uid) 
                            {
                                //分类1系统增加2系统扣除3余额提币4提币驳回5节点获得
                                $usdtData[] = [
                                    'ordernum' => $ordernum,
                                    'user_id' => $uid,
                                    'from_user_id' => 0,
                                    'type' => 1,
                                    'cate' => 5,
                                    'total' => $avgUsdt,
                                    'msg' => '节点获得',
                                    'content' => '节点获得',
                                    'created_at' => $datetime,
                                    'updated_at' => $datetime,
                                ];
                            }
                            
                            NodePool::query()->where('id', 1)->decrement('pool', $totalSub);
                            
                            $userIds = array_chunk($userIds, 500);
                            foreach ($userIds as $ids) {
                                User::query()->whereIn('id', $ids)->update([
                                    'usdt'=> DB::raw("`usdt`+{$avgUsdt}")
                                ]);
                            }
                            
                            $usdtData = array_chunk($usdtData, 1000);
                            foreach ($usdtData as $ndata) {
                                UserUsdt::query()->insert($ndata);
                            }
                            
                        }
                    }
                }
            }
            
            $MyRedis = new MyRedis();
            $MyRedis->del_lock($lockKey);
        }
    }

}
