<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;
use App\Models\FishRod;
use App\Models\FishRodOrder;
use App\Models\User;
use App\Models\OrderLog;
use App\Models\RankRodLog;
use App\Models\UserFishRod;
use App\Models\TicketConfig;
use App\Models\UserTicket;


class SendTicket extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    public function handle(array $input)
    {
        $in = $input;
        
        $ticket_id = $in['ticket_id'] ?? 0;
        
        $lockKey = 'SendTicket';
        $MyRedis = new MyRedis();
//         $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 600);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
        
        if (!isset($in['wallet']) || !$in['wallet']) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('请输入钱包地址');
        }
        
//         if (!isset($in['num']) || intval($in['num'])<=0) {
//             $MyRedis->del_lock($lockKey);
//             return $this->response()->error('请输入数量');
//         }
//         $num = intval($in['num']);
        $num = 1;
        
        $wallet = strtolower($in['wallet']);
        
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        $time = time();
        $datetime = date('Y-m-d H:i:s', $time);
        
        $ordernum = get_ordernum();
        $TicketConfig = TicketConfig::query()->where('id', $ticket_id)->first();
        if ($TicketConfig && $num>0)
        {
            $TicketData = [];
            for ($i=1; $i<=$num; $i++)
            {
                $TicketData[] = [
                    'user_id' => $user->id,
                    'ticket_id' => $ticket_id,
                    'source_type' => 2, //来源1平台购买2平台赠送3用户赠送
                    'ordernum' => $ordernum,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                ];
            }
            UserTicket::query()->insert($TicketData);
        }
        
//         TicketConfig::query()->where('id', $ticket_id)->increment('ticket_sale', $order->num);
        
        $MyRedis->del_lock($lockKey);
        
        return $this
            ->response()
            ->success('操作成功')
            ->refresh();
    }
    
    /**
     * Build a form here.
     */
    public function form()
    {
        $TicketConfig = TicketConfig::query()->orderBy('ticket_price','asc')->get()->pluck('ticket_price','id');
        
        $this->text('wallet','钱包地址')->required();
        $this->select('ticket_id','入场券')->options($TicketConfig)->required();
//         $this->number('num','数量')->min(1)->default(1)->required();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'rod_lv' => 1,
            'wallet' => '',
            'num' => 1,
        ];
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
}
