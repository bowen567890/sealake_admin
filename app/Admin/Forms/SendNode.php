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
use App\Models\NodeConfig;
use App\Models\NodeOrder;
use App\Models\RankConfig;


class SendNode extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    
    public function handle(array $input)
    {
        $in = $input;
        
        $lv = $in['lv'] ?? 0;
        
        $lockKey = 'SendNode';
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
        
        $wallet = strtolower($in['wallet']);
        
        $user = User::query()->where('wallet', $wallet)->first();
        if (!$user) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户不存在');
        }
        
        if ($user->node_rank>0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('用户已经是节点');
        }
        
        $NodeConfig = NodeConfig::query()->where('lv', $lv)->first();
        if (!$NodeConfig) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('系统维护');
        }
        
        if ($NodeConfig->stock<=0) {
            $MyRedis->del_lock($lockKey);
            return $this->response()->error('库存不足');
        }
        
        $ordernum = get_ordernum();
        
        $order = new NodeOrder();
        $order->ordernum = $ordernum;
        $order->user_id = $user->id;
        $order->lv = $NodeConfig->lv;
        $order->price = $NodeConfig->price;
        $order->gift_ticket_id = $NodeConfig->gift_ticket_id;
        $order->gift_ticket_num = $NodeConfig->gift_ticket_num;
        $order->gift_rank_id = $NodeConfig->gift_rank_id;
        $order->static_rate = $NodeConfig->static_rate;
        $order->pay_type = 0;
        $order->source_type = 2;    //来源1平台购买2平台赠送
        $order->save();
        
        $datetime = date('Y-m-d H:i:s');
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
        $nodeRankArr = [1=>'精英节点',2=>'核心节点',3=>'创世节点'];
        $this->text('wallet','钱包地址')->required();
        $this->select('lv','赠送节点')->options($nodeRankArr)->required();
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
            'wallet' => ''
        ];
    }
    
    /**
     * 获取用户信息
     */
    protected function getUser($id) {
        return User::query()->where('id', $id)->first();
    }
    
}
