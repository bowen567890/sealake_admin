<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\MyRedis;
use App\Models\Banner;
use App\Models\Bulletin;
use App\Models\User;
use App\Models\MainCurrency;
use App\Models\OrderLog;
use App\Models\PowerConf;
use App\Models\News;
use App\Models\UserNft;
use App\Models\NftConf;
use App\Models\UserVvStatic;
use App\Models\UserVvDynamic;
use App\Models\UserVv;
use App\Models\JackpotConfig;
use App\Models\JackpotRanking;
use App\Models\FeeOrder;
use App\Models\UserVvQuantifyWait;
use App\Models\UserVvQuantify;
use App\Models\UserVvJackpot;
use GuzzleHttp\Client;
use App\Models\Withdraw;
use App\Models\EnergyOrderLog;
use App\Models\IgniteOrderLog;
use App\Models\IgniteOrder;
use App\Models\SignConfig;

class IndexController extends Controller
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
        $data['wallet'] = $user->wallet;
        $basic_power_usdt = @bcadd(config('basic_power_usdt'), '0', 2);
        $power_multiple_num = @bcadd(config('power_multiple_num'), '0', 2);
        $data['basic_power_usdt'] = $basic_power_usdt>0 ? $basic_power_usdt : 100;
        $data['basic_power_multiple'] = intval(config('basic_power_multiple'))>0 ? intval(config('basic_power_multiple')) : 1;
        $data['power_multiple_num'] = $power_multiple_num>0 ? $power_multiple_num : 1;
        
        $data['last_sign_time'] = $user->last_sign_time;
        $data['sign_interval_day'] = intval(config('sign_interval_day'));
        $data['sign_config'] = SignConfig::GetListCache();
        
        return responseJson($data);
    }
    
    public function newsList(Request $request)
    {
        $user = auth()->user();
        $in = $request->input();
        
        $pageNum = isset($in['page_num']) && intval($in['page_num'])>0 ? intval($in['page_num']) : 10;
        $page = isset($in['page']) ? intval($in['page']) : 1;
        $page = $page<=0 ? 1 : $page;
        $offset = ($page-1)*$pageNum;
        
        $list = News::query()
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($pageNum)
            ->get(['id','title','content','created_at'])
            ->toArray();
        if ($list) {
//             foreach ($list as &$v) {
//             }
        }
        return responseJson($list);
    }
    
    public function newsInfo(Request $request)
    {
        $in = $request->input();
        
        $id = isset($in['id']) && intval($in['id'])>0 ? intval($in['id']) : 0;
        $info = News::query()
            ->where('id', $id)
            ->first(['id','title','content','created_at']);
        return responseJson($info);
    }
}
