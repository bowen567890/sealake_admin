<?php
namespace App\Console\Commands;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MyRedis;
use App\Models\MainCurrency;
use App\Models\RankConfig;
use App\Models\DepthConfig;
use App\Models\LuckyPool;
use App\Models\NodePool;
use App\Models\UserPower;
use App\Models\UserDogbee;
use App\Models\SeeConfig;
use App\Models\SyncPower;
use App\Models\SignOrder;
use App\Models\ManageRankConfig;
use App\Models\UserUsdt;


class SyncPowerEvent extends Command
{
    // 自定义脚本命令签名
    protected $signature = 'command:SyncPowerEvent';

    // 自定义脚本命令描述
    protected $description = '算力事件';


    // 创建一个新的命令实例
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        $lockKey = 'command:SyncPowerEvent';
        $MyRedis = new MyRedis();
//                             $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 600);
        if ($lock)
        {
            $list = User::query()
                    ->join('sync_power as s', 'users.id', '=', 's.user_id')
                    ->where('s.status', '=', 0)
                    ->orderBy('s.id', 'desc')
                    ->get([
                        's.id','s.user_id','s.order_id','s.type','s.status','s.usdt','s.power','s.ordernum',
                        'users.parent_id','users.is_valid','users.level','users.manage_rank','users.path','s.created_at'
                    ])
                    ->toArray();
            if ($list)
            {
                $time = time();
                $date = date('Y-m-d H:i:s', $time);
                
                //等级配置
                $rankConf = RankConfig::GetListCache();
                $rankConf = $rankConf ? array_column($rankConf, null, 'lv') : [];
                
                //管理等级
                $manageConfig = ManageRankConfig::GetListCache();
                $manageConfig = $manageConfig ? array_column($manageConfig, null, 'lv') : [];
                
                //推荐配置
                $depthConf = DepthConfig::GetListCache();
                $depthConf = $depthConf ? array_column($depthConf, null, 'depth') : [];
                $endDepth = 0;
                //获取最后层级
                if ($depthConf) {
                    $tmpDepth = end($depthConf);
                    $endDepth = $tmpDepth['depth'];
                }
                
                //见点配置
                $seeConfig = SeeConfig::GetListCache();
                $min_depth = SeeConfig::query()->orderBy('min_depth', 'asc')->value('min_depth');
                $max_depth = SeeConfig::query()->orderBy('max_depth', 'desc')->value('max_depth');
                $min_depth = intval($min_depth);
                $max_depth = intval($max_depth);
               
                //抽奖池配置
                $LuckyPool = LuckyPool::query()->where('id', 1)->first();
                
                //DOGBEE价格
                $dogebeeCurrency = MainCurrency::query()->where('id', 3)->first(['rate','contract_address']);
                $dogebee_price = $dogebeeCurrency->rate;
                //DOGBEE指导价格
                $dogebee_guidance_price = bcadd(config('dogebee_guidance_price'), '0', 10);
                
                //签到价格系数
                $sign_price_rate = @bcadd(config('sign_price_rate'), '0', 6);
                $sign_price_rate = bccomp($sign_price_rate, '0', 6)<=0 ? '1.2' : $sign_price_rate;
                
                //dogbee数量 = 算力×签到算力比率/（DOGBEE指导价格×签到价格系数（签到价格系数后端可调））
                $priceRate = bcmul($dogebee_guidance_price, $sign_price_rate, 8);
                
                $logIds = $dogbeeData = $powerData = $usdtData = $userList = [];
                $userModel = new User();
                
                foreach ($list as $val) 
                {
                    $datetime = $val['created_at'];
                    //批量更新记录状态
                    $logIds[] = $val['id'];
                    
                    //类型1购买算力2算力签到
                    if ($val['type']==1) 
                    {
                        //个人获得算力
                        $uup = [];
                        $uup['power'] = DB::raw("`power`+{$val['power']}");
                        //有效用户
                        if ($val['is_valid']==0)
                        {
                            $uup['is_valid'] = 1;
                            //上级增加有效矿工
                            if ($val['parent_id']>0) {
                                User::query()->where('id', $val['parent_id'])->increment('zhi_valid', 1);
                            }
                        }
                        User::query()->where('id', $val['user_id'])->update($uup);
                        
                        //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速
                        $powerData[] = [
                            'ordernum' => $val['ordernum'],
                            'user_id' => $val['user_id'],
                            'from_user_id' => 0,
                            'type' => 1,
                            'cate' => 4,
                            'total' => $val['power'],
                            'msg' => '购买算力',
                            'content' => '购买算力',
                            'created_at' => $datetime,
                            'updated_at' => $datetime,
                        ];
                        
                        //个人业绩
                        $userModel->handleSelfYeji($val['user_id'], $val['usdt']);
                        //团队
                        if ($val['path'])
                        {
                            //上级增加累计推广业绩|判断抽奖次数
                            $userModel->handlePushYeji($val['parent_id'], $val['usdt'], $LuckyPool);
                            //团队业绩
                            $userModel->handleTeamYeji($val['path'], $val['usdt']);
                            //用户等级更新
                            $userModel->UpdateUserRank($val['path'], $rankConf);
                            
                            //管理等级奖励 级差制
                            //上级信息
                            $parentIds = explode('-',trim($val['path'],'-'));
                            $parentIds = array_filter($parentIds);
                            if ($parentIds && $manageConfig)
                            {
                                $manageRank = 0;
                                $manageReward = '0';
                                $puserList = User::query()
                                    ->where('manage_rank', '>', 0)
                                    ->whereIn('id', $parentIds)
                                    ->orderBy('level', 'desc')
                                    ->get(['id','level','manage_rank','is_valid'])
                                    ->toArray();
                                if ($puserList)
                                {
                                    foreach ($puserList as $puser)
                                    {
                                        if (isset($manageConfig[$puser['manage_rank']]) && $puser['manage_rank']>$manageRank) 
                                        {
                                            $manageRank = $puser['manage_rank'];
                                            if (bccomp($manageConfig[$puser['manage_rank']]['reward_usdt'], $manageReward, 2)>0) 
                                            {
                                                $rewardRate = bcsub($manageConfig[$puser['manage_rank']]['reward_usdt'], $manageReward, 2);
                                                //管理极差是投资金额×系数比率
                                                $rewardUsdt = bcmul($val['usdt'], $rewardRate, 2);
                                                $manageReward = $manageConfig[$puser['manage_rank']]['reward_usdt'];
                                                
                                                if (!isset($userList[$puser['id']])) {
                                                    $userList[$puser['id']] = [
                                                        'user_id' => $puser['id'],
                                                        'usdt' => '0'
                                                    ];
                                                }
                                                $userList[$puser['id']]['usdt'] = bcadd($userList[$puser['id']]['usdt'], $rewardUsdt, 6);
                                                
                                                //分类1系统增加2系统扣除3余额提币4提币驳回5节点获得6管理级奖
                                                $usdtData[] = [
                                                    'ordernum' => $val['ordernum'],
                                                    'user_id' => $puser['id'],
                                                    'from_user_id' => $val['user_id'],
                                                    'type' => 1,
                                                    'cate' => 6,
                                                    'total' => $rewardUsdt,
                                                    'msg' => '管理级奖',
                                                    'content' => "管理级{$manageConfig[$puser['manage_rank']]['name']}奖励",
                                                    'created_at' => $datetime,
                                                    'updated_at' => $datetime,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } 
                    else if ($val['type']==2) 
                    {
                        //每日签到依据指导价格释放，释放dogbee，动静算力不扣（指导价后台可以调）
                        if (bccomp($val['usdt'], '0', 2)>0) {
                            //自己增加签到业绩
                            $userModel->handleSignYeji($val['user_id'], $val['usdt'], $LuckyPool);
                        }
                        
                        if (bccomp($val['power'], '0', 6)>0 && bccomp($priceRate, '0', 8)>0) 
                        {
                            //个人点火加速塌缩算力
                            $user = User::query()->where('id', $val['user_id'])->first(['id','power']);
                            //个人算力大于等于平均的算力
                            if (bccomp($user->power, $val['power'], 6)>=0) {
                                $subPower = $val['power'];
                            } else {
                                $subPower = $user->power;
                            }
                            
                            $userDogbee = '0';
                            if (bccomp($subPower, '0', 6)>0) 
                            {
                                //dogbee数量 = 算力/（价格×签到价格系数（签到价格系数后端可调））
                                $userDogbee = bcdiv($subPower, $priceRate, 6);
                                
                                $rup = [];
                                
                                //动静算力不扣
//                                 $rup['power'] = DB::raw("`power`-{$subPower}");
//                                 //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速
//                                 $powerData[] = [
//                                     'ordernum' => $val['ordernum'],
//                                     'user_id' => $val['user_id'],
//                                     'from_user_id' => 0,
//                                     'type' => 2,
//                                     'cate' => 5,
//                                     'total' => $subPower,
//                                     'msg' => '签到扣除',
//                                     'content' => "签到扣除",
//                                     'created_at' => $datetime,
//                                     'updated_at' => $datetime,
//                                 ];
                               
                                //用户获得的Dogbee
                                if (bccomp($userDogbee, '0', 6)>0)
                                {
                                    $rup['dogbee'] = DB::raw("`dogbee`+{$userDogbee}");
                                    //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速
                                    $dogbeeData[] = [
                                        'ordernum' => $val['ordernum'],
                                        'user_id' => $val['user_id'],
                                        'from_user_id' => 0,
                                        'type' => 1,
                                        'cate' => 5,
                                        'total' => $userDogbee,
                                        'msg' => '签到获得',
                                        'content' => "签到获得",
                                        'created_at' => $datetime,
                                        'updated_at' => $datetime,
                                    ];
                                }
                                
                                if ($rup) {
                                    User::query()->where('id', $val['user_id'])->update($rup);
                                }
                            }
                            SignOrder::query()->where('id', $val['order_id'])->update([
                                'dogbee'=>$userDogbee,
                                'coin_price'=>$dogebee_price,
                                'sign_price_rate'=>$sign_price_rate,
                            ]);
                            
                           
                            //推荐 | 见点 | 团队
                            if ($val['path'])
                            {
                                //上级信息
                                $parentIds = explode('-',trim($val['path'],'-'));
                                $parentIds = array_filter($parentIds);
                                if ($parentIds)
                                {
                                    $minLevel = $val['level']-$endDepth;
                                    //推荐奖
                                    $puserList = User::query()
                                        ->whereIn('id', $parentIds)
                                        ->where('level', '>=', $minLevel)
                                        ->orderBy('level', 'desc')
                                        ->get(['id','level','zhi_num','is_valid','power'])
                                        ->toArray();
                                    if ($puserList)
                                    {
                                        foreach ($puserList as $puser)
                                        {
                                            //层级奖励
                                            $diffLevel = $val['level']-$puser['level'];
                                            if (isset($depthConf[$diffLevel]) && bccomp($depthConf[$diffLevel]['rate'], '0', 2)>0)
                                            {
                                                //判断推荐人数 此处是否需要判断推荐是否有效用户
                                                $depthPower = bcmul($val['power'], $depthConf[$diffLevel]['rate'], 6);
                                                if (bccomp($depthPower, '0', 6)>0) 
                                                {
                                                    //个人算力大于等于平均的算力
                                                    if (bccomp($puser['power'], $depthPower, 6)>=0) {
                                                        $subPower = $depthPower;
                                                    } else {
                                                        $subPower = $puser['power'];
                                                    }
                                                    
                                                    if (bccomp($subPower, '0', 6)>0)
                                                    {
                                                        //dogbee数量 = 算力×签到算力比率/（价格×签到价格系数（签到价格系数后端可调））
                                                        $userDogbee = bcdiv($subPower, $priceRate, 6);
                                                        
                                                        $rup = [];
                                                        
                                                        //动静算力不扣
//                                                         if (bccomp($subPower, '0', 6)>0)
//                                                         {
//                                                             $rup['power'] = DB::raw("`power`-{$subPower}");
//                                                             //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速
//                                                             $powerData[] = [
//                                                                 'ordernum' => $val['ordernum'],
//                                                                 'user_id' => $puser['id'],
//                                                                 'from_user_id' => $val['user_id'],
//                                                                 'type' => 2,
//                                                                 'cate' => 6,
//                                                                 'total' => $subPower,
//                                                                 'msg' => '推荐加速',
//                                                                 'content' => "推荐加速",
//                                                                 'created_at' => $datetime,
//                                                                 'updated_at' => $datetime,
//                                                             ];
//                                                         }
                                                        
                                                        //用户获得的Dogbee
                                                        if (bccomp($userDogbee, '0', 6)>0)
                                                        {
                                                            $rup['dogbee'] = DB::raw("`dogbee`+{$userDogbee}");
                                                            //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速
                                                            $dogbeeData[] = [
                                                                'ordernum' => $val['ordernum'],
                                                                'user_id' => $puser['id'],
                                                                'from_user_id' => $val['user_id'],
                                                                'type' => 1,
                                                                'cate' => 6,
                                                                'total' => $userDogbee,
                                                                'msg' => '推荐加速',
                                                                'content' => "推荐加速",
                                                                'created_at' => $datetime,
                                                                'updated_at' => $datetime,
                                                            ];
                                                        }
                                                        if ($rup) {
                                                            User::query()->where('id', $puser['id'])->update($rup);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    //见点奖励 直推有效用户
                                    if ($seeConfig)
                                    {
                                        $minLevel = $val['level']-$max_depth;
                                        $maxLevel = $val['level']-$min_depth;
                                        //推荐奖
                                        $puserList = User::query()
                                            ->whereIn('id', $parentIds)
                                            ->where('level', '<=', $maxLevel)
                                            ->where('level', '>=', $minLevel)
                                            ->orderBy('level', 'desc')
                                            ->get(['id','is_valid','level','zhi_valid','power'])
                                            ->toArray();
                                        if ($puserList)
                                        {
                                            foreach ($puserList as $puser)
                                            {
                                                //层级奖励
                                                $diffLevel = $val['level']-$puser['level'];
                                                if ($diffLevel>=$min_depth && $diffLevel<=$max_depth) 
                                                {
                                                    $seeRate = '0';
                                                    foreach ($seeConfig as $conf)
                                                    {
                                                        if ($puser['zhi_valid']>=$conf['num'] && $diffLevel>=$conf['min_depth'] && $diffLevel<=$conf['max_depth']) 
                                                        {
                                                            $seeRate = $conf['rate'];
                                                        }
                                                    }
                                                    
                                                    if (bccomp($seeRate, '0', 2)>0) 
                                                    {
                                                        //判断推荐人数 此处是否需要判断推荐是否有效用户
                                                        $seePower = bcmul($val['power'], $seeRate, 6);
                                                        if (bccomp($seePower, '0', 6)>0)
                                                        {
                                                            //个人算力大于等于平均的算力
                                                            if (bccomp($puser['power'], $seePower, 6)>=0) {
                                                                $subPower = $seePower;
                                                            } else {
                                                                $subPower = $puser['power'];
                                                            }
                                                            
                                                            if (bccomp($subPower, '0', 6)>0)
                                                            {
                                                                //dogbee数量 = 算力×签到算力比率/（价格×签到价格系数（签到价格系数后端可调））
                                                                $userDogbee = bcdiv($subPower, $priceRate, 6);
                                                                
                                                                $rup = [];
                                                                
                                                                //动静算力不扣
//                                                                 if (bccomp($subPower, '0', 6)>0)
//                                                                 {
//                                                                     $rup['power'] = DB::raw("`power`-{$subPower}");
//                                                                     //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速
//                                                                     $powerData[] = [
//                                                                         'ordernum' => $val['ordernum'],
//                                                                         'user_id' => $puser['id'],
//                                                                         'from_user_id' => $val['user_id'],
//                                                                         'type' => 2,
//                                                                         'cate' => 7,
//                                                                         'total' => $subPower,
//                                                                         'msg' => '见点加速',
//                                                                         'content' => "见点{$diffLevel}层加速",
//                                                                         'created_at' => $datetime,
//                                                                         'updated_at' => $datetime,
//                                                                     ];
//                                                                 }
                                                                
                                                                //用户获得的Dogbee
                                                                if (bccomp($userDogbee, '0', 6)>0)
                                                                {
                                                                    $rup['dogbee'] = DB::raw("`dogbee`+{$userDogbee}");
                                                                    //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速
                                                                    $dogbeeData[] = [
                                                                        'ordernum' => $val['ordernum'],
                                                                        'user_id' => $puser['id'],
                                                                        'from_user_id' => $val['user_id'],
                                                                        'type' => 1,
                                                                        'cate' => 7,
                                                                        'total' => $userDogbee,
                                                                        'msg' => '见点加速',
                                                                        'content' => "见点{$diffLevel}层加速",
                                                                        'created_at' => $datetime,
                                                                        'updated_at' => $datetime,
                                                                    ];
                                                                }
                                                                if ($rup) {
                                                                    User::query()->where('id', $puser['id'])->update($rup);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    //团队奖励
                                    if ($rankConf)
                                    {
                                        $puserList = User::query()
                                            ->where('rank', '>', 0)
                                            ->whereIn('id', $parentIds)
                                            ->orderBy('level', 'desc')
                                            ->get(['id','is_valid','rank','level','zhi_valid','power'])
                                            ->toArray();
                                        if ($puserList)
                                        {
                                            $currentRank = 0;
                                            $currentRate = '0';
                                            
                                            foreach ($puserList as $puser)
                                            {
                                                if ($puser['rank']>$currentRank && isset($rankConf[$puser['rank']]))
                                                {
                                                    $currentRank = $puser['rank'];
                                                    if (bccomp($rankConf[$puser['rank']]['rate'], '0', 2)>0 && bccomp($rankConf[$puser['rank']]['rate'], $currentRate, 2)>0)
                                                    {
                                                        $diffRate = bcsub($rankConf[$puser['rank']]['rate'], $currentRate, 2);
                                                        $currentRate = $rankConf[$puser['rank']]['rate'];
                                                        
                                                        $rankPower = bcmul($val['power'], $diffRate, 6);
                                                        if (bccomp($rankPower, '0', 6)>0)
                                                        {
                                                            //个人算力大于等于平均的算力
                                                            if (bccomp($puser['power'], $rankPower, 6)>=0) {
                                                                $subPower = $rankPower;
                                                            } else {
                                                                $subPower = $puser['power'];
                                                            }
                                                            
                                                            if (bccomp($subPower, '0', 6)>0)
                                                            {
                                                                //dogbee数量 = 算力×签到算力比率/（价格×签到价格系数（签到价格系数后端可调））
                                                                $userDogbee = bcdiv($subPower, $priceRate, 6);
                                                                
                                                                $rup = [];
                                                                //动静算力不扣
//                                                                 if (bccomp($subPower, '0', 6)>0)
//                                                                 {
//                                                                     $rup['power'] = DB::raw("`power`-{$subPower}");
//                                                                     //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速
//                                                                     $powerData[] = [
//                                                                         'ordernum' => $val['ordernum'],
//                                                                         'user_id' => $puser['id'],
//                                                                         'from_user_id' => $val['user_id'],
//                                                                         'type' => 2,
//                                                                         'cate' => 8,
//                                                                         'total' => $subPower,
//                                                                         'msg' => '团队加速',
//                                                                         'content' => "团队V{$puser['rank']}加速",
//                                                                         'created_at' => $datetime,
//                                                                         'updated_at' => $datetime,
//                                                                     ];
//                                                                 }
                                                                
                                                                //用户获得的Dogbee
                                                                if (bccomp($userDogbee, '0', 6)>0)
                                                                {
                                                                    $rup['dogbee'] = DB::raw("`dogbee`+{$userDogbee}");
                                                                    //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速
                                                                    $dogbeeData[] = [
                                                                        'ordernum' => $val['ordernum'],
                                                                        'user_id' => $puser['id'],
                                                                        'from_user_id' => $val['user_id'],
                                                                        'type' => 1,
                                                                        'cate' => 8,
                                                                        'total' => $userDogbee,
                                                                        'msg' => '团队加速',
                                                                        'content' => "团队V{$puser['rank']}加速",
                                                                        'created_at' => $datetime,
                                                                        'updated_at' => $datetime,
                                                                    ];
                                                                }
                                                                if ($rup) {
                                                                    User::query()->where('id', $puser['id'])->update($rup);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                if ($logIds) {
                    $logIds = array_chunk($logIds, 1000);
                    foreach ($logIds as $ids) {
                        SyncPower::query()->whereIn('id', $ids)->update(['status'=>1]);
                    }
                }   
                
                if ($userList) {
                    foreach ($userList as $uval) {
                        User::query()->where('id', $uval['user_id'])->increment('usdt', $uval['usdt']);
                    }
                }
                
                if ($usdtData) {
                    $usdtData = array_chunk($usdtData, 1000);
                    foreach ($usdtData as $ndata) {
                        UserUsdt::query()->insert($ndata);
                    }
                }
                
                if ($powerData) {
                    $powerData = array_chunk($powerData, 1000);
                    foreach ($powerData as $ndata) {
                        UserPower::query()->insert($ndata);
                    }
                }
                
                if ($dogbeeData) {
                    $dogbeeData = array_chunk($dogbeeData, 1000);
                    foreach ($dogbeeData as $ndata) {
                        UserDogbee::query()->insert($ndata);
                    }
                }
            }
            
            $MyRedis = new MyRedis();
            $MyRedis->del_lock($lockKey);
        }
    }
}
