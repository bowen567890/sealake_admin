<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Illuminate\Http\Request;
use App\Models\MainCurrency;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\ManageRankConfig;
use App\Models\ManageOperateLog;

class ManageController extends Controller
{
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function index(Request $request)
    {
        $user = auth()->user();
        $is_show_manage = ManageRankConfig::query()
            ->where('lv', $user->manage_rank)
            ->value('is_show');
        
        if ($is_show_manage!=1) {
            return responseValidateError(__('error.敬请期待'));
        }
        
        $data = [];
        $data['manage_rank'] = $user->manage_rank;
        $data['is_show_manage'] = intval($is_show_manage);
        
        $data['config_list'] = ManageRankConfig::query()
            ->where('lv', '<=', $user->manage_rank)
            ->where('backend_set', 0)
            ->get(['lv','name'])
            ->toArray();
        
        return responseJson($data);
    }
    
    
    public function operate(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        if (!isset($in['wallet']) || !$in['wallet'])  return responseValidateError(__('error.请输入钱包地址'));
        $wallet = trim($in['wallet']);
        if (!checkBnbAddress($wallet)) {
            return responseValidateError(__('error.钱包地址有误'));
        }
        
        $lv = 0;
        if (isset($in['lv'])) {
            $lv = intval($in['lv']);
        }
        
        $lockKey = 'user:info:'.$user->id;
        $MyRedis = new MyRedis();
//                                 $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 20);
        if(!$lock){
            return responseValidateError(__('error.操作频繁'));
        }
        
        $is_show_manage = ManageRankConfig::query()
            ->where('lv', $user->manage_rank)
            ->value('is_show');
        if ($is_show_manage!=1) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.敬请期待'));
        }
        
        $user_id = $user->id;
        if($user->path) {
            $path = $user->path."{$user->id}-";
        } else {
            $path = "-{$user->id}-";
        }
        
        $targetUser = User::query()
            ->where('wallet', $wallet)
            ->where('path', 'like', "{$path}%")
            ->first();
        if (!$targetUser) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.只能指定伞下用户'));
        }
        
        if ($lv>$user->manage_rank) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.不能超过自己级别'));
        }
        
        $backend_set = ManageRankConfig::query()
            ->where('lv', $lv)
            ->where('backend_set', 0)
            ->first();
        if (!$backend_set) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.状态异常'));
        }
        
        $backend_set2 = ManageRankConfig::query()
            ->where('lv', $targetUser->manage_rank)
            ->where('backend_set', 1)
            ->first();
        if ($backend_set2) {
            $MyRedis->del_lock($lockKey);
            return responseValidateError(__('error.此用户不可操作'));
        }
        
        if ($targetUser->manage_rank!=$lv) 
        {
            $ManageOperateLog = new ManageOperateLog();
            $ManageOperateLog->user_id = $user->id;
            $ManageOperateLog->target_id = $targetUser->id;
            $ManageOperateLog->old_rank = $targetUser->manage_rank;
            $ManageOperateLog->new_rank = $lv;
            $ManageOperateLog->is_backend = 0;  //后台操作
            $ManageOperateLog->save();
            
            $targetUser->manage_rank = $lv;
            $targetUser->save();
        }
        
        $MyRedis->del_lock($lockKey);
        
        return responseJson();
    }
    
    
    public function operateLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $list = ManageOperateLog::with(['targetuser'])
            ->where($where)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['user_id','target_id','old_rank','new_rank','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v) 
            {
                $v['target_wallet'] = '';
                if ($v['targetuser']) {
                    $v['target_wallet'] = $v['targetuser']['wallet'];
                }
                unset($v['targetuser']);
            }
        }
        return responseJson($list);
    }
}
