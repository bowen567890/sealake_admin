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

class SyncNodePoolSuper extends Command
{
    protected $signature = 'command:SyncNodePoolSuper';

    protected $description = '超级节点池子分发';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lockKey = 'command:SyncNodePoolSuper';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 1800);
        if ($lock)
        {
            $NodePool = NodePool::query()->where('id', 1)->first();
            if ($NodePool && bccomp($NodePool->super_pool, '0', 6)>0 && bccomp($NodePool->super_give_rate, '0', 4)>0)
            {
                $totalNum = bcmul($NodePool->super_pool, $NodePool->super_give_rate, 6);
                if (bccomp($totalNum, '0', 6)>0) 
                {
                    $userIds = User::query()
                        ->where('super_node', 1)
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
                                //分类1系统增加2系统扣除3余额提币4提币驳回5普通节点获得6管理级奖7超级节点获得
                                $usdtData[] = [
                                    'ordernum' => $ordernum,
                                    'user_id' => $uid,
                                    'from_user_id' => 0,
                                    'type' => 1,
                                    'cate' => 7,
                                    'total' => $avgUsdt,
                                    'msg' => '超级节点',
                                    'content' => '超级节点',
                                    'created_at' => $datetime,
                                    'updated_at' => $datetime,
                                ];
                            }
                            
                            NodePool::query()->where('id', 1)->decrement('super_pool', $totalSub);
                            
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
