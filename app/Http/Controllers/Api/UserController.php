<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Illuminate\Http\Request;
use App\Models\MainCurrency;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\UserUsdt;
use App\Models\RankConfig;
use App\Models\UserPower;
use App\Models\UserPowerList;
use App\Models\UserAleo;
use App\Models\UserEntt;
use App\Models\UserDino;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\AssetPackage;
use App\Models\EcoPackage;
use App\Models\UserVoucher;
use App\Models\UserVoucherDino;
use App\Models\AllocationApply;
use App\Models\UserMh;
use App\Models\UserVv;
use App\Models\UserVvQuantify;
use App\Models\UserEnergy;
use App\Models\UserSpacex;
use App\Models\UserDogbee;
use App\Models\ManageRankConfig;
use App\Models\UserPoint;

class UserController extends Controller
{
    public $host = '';
    
    public function __construct()
    {
        parent::__construct();
        $this->host =  $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function info(Request $request)
    {
        $user = auth()->user();
        
        $is_show_manage = ManageRankConfig::query()->where('lv', $user->manage_rank)->value('is_show');
        
        $data = [];
        $data['id'] = $user->id;
        $data['wallet'] = $user->wallet;
        $data['code'] = $user->code;
        $data['usdt'] = $user->usdt;
        $data['power'] = $user->power;
        $data['dogbee'] = $user->dogbee;
        $data['point'] = $user->point;
        $data['rank'] = $user->rank;
        $data['is_node'] = $user->is_node;
        $data['super_node'] = $user->super_node;
        $data['is_branch'] = $user->is_branch;
        $data['is_merchant'] = $user->is_merchant;
        $data['manage_rank'] = $user->manage_rank;
        $data['is_show_manage'] = intval($is_show_manage);
        $data['lottery_num'] = $user->lottery_num;
        $data['last_sign_time'] = $user->last_sign_time;
        $data['next_sign_time'] = '';
        $data['next_sign_time_stamp'] = 0;
        $data['zhi_num'] = $user->zhi_num;
        $data['group_num'] = $user->group_num;
        $data['self_yeji'] = $user->self_yeji;
        $data['team_yeji'] = $user->team_yeji;
        $data['zhi_yeji'] = $user->zhi_yeji;
        $data['total_yeji'] = $user->total_yeji;
        $data['headimgurl'] = getImageUrl($user->headimgurl);
        $data['collection_address'] = config('collection_address');
        
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
        $data['small_yeji'] = $small_yeji;
        $is_can_super = 0;
        if ($user->super_node==1) {
            $is_can_super = 0;
        } else {
            $super_node_community = @bcadd(config('super_node_community'), '0', 2);
            if (bccomp($small_yeji, $super_node_community, 2)>=0) {
                $is_can_super = 1;
            }
        }
        $data['is_can_super'] = $is_can_super;
        
        $withdraw_fee_bnb = @bcadd(config('withdraw_fee_bnb'), '0', 6);
        $withdraw_fee_bnb = bccomp($withdraw_fee_bnb, '0', 6)>0 ? $withdraw_fee_bnb : '0.0015';
        $data['withdraw_fee_bnb'] = $withdraw_fee_bnb;
        
        $MainCurrency = MainCurrency::query()->get(['id','rate'])->toArray();
        $MainCurrency = array_column($MainCurrency, null, 'id');
        $data['dogbee_price'] = $MainCurrency[3]['rate'];
        
        $time = time();
        if ($user->last_sign_time)
        {
            $sign_interval_day = intval(config('sign_interval_day'));
            $sign_interval_day = $sign_interval_day>0 ? $sign_interval_day : 7;
            $last_sign_time = strtotime($user->last_sign_time);
            $next_sign_time = $last_sign_time+86400*$sign_interval_day;
            $data['next_sign_time'] = date('Y-m-d H:i:s', $next_sign_time);
            $data['next_sign_time_stamp'] = $next_sign_time;
        }
        
        return responseJson($data);
    }
    
    public function teamList(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $pageNum = $pageNum>=20 ? $pageNum : $pageNum;
        $offset = ($page-1)*$pageNum;
        
        $user_id = $user->id;
        if($user->path) {
            $path = $user->path."{$user->id}-";
        } else {
            $path = "-{$user->id}-";
        }
        
        $list = User::query()
            ->where('parent_id', '=', $user_id)
            //             ->where('path', 'like', "{$path}%")
            ->orderBy('id','asc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['wallet','power','self_yeji','team_yeji','zhi_yeji','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$val) {
                $val['wallet'] =  substr_replace($val['wallet'],'*****', 3, -3);
                $val['power'] = bcadd($val['power'], '0', 2);
                $val['self_yeji'] = bcadd($val['self_yeji'], '0', 2);
                $val['team_yeji'] = bcadd($val['team_yeji'], '0', 2);
            }
        }
        
        return responseJson($list);
    }
    
    
    public function usdtLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserUsdt::query()
            ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','type','total','cate','msg','content','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.USDT类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    public function powerLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserPower::query()
        ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','type','total','cate','msg','content','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.POWER类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    public function dogbeeLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserDogbee::query()
        ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
        ->offset($offset)
        ->limit($pageNum)
        ->get(['id','type','total','cate','msg','content','created_at'])
        ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.DOGBEE类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    public function pointLog(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $where['user_id'] = $user->id;
        
        $cate = [];
        if (isset($in['cate']) && $in['cate'] && is_array($in['cate'])) {
            $cate = array_filter($in['cate']);
        }
        
        $list = UserPoint::query()
        ->where($where);
        if ($cate) {
            $list = $list->whereIn('cate', $cate);
        }
        $list = $list->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','type','total','cate','msg','content','created_at'])
            ->toArray();
        if ($list) {
            foreach ($list as &$v) {
                $v['content'] = $v['msg'] = __("error.POINT类型{$v['cate']}");
            }
        }
        return responseJson($list);
    }
    
    
}
