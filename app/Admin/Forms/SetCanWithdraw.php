<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use App\Models\User;
use App\Models\MyRedis;
use Illuminate\Support\Facades\DB;

class SetCanWithdraw extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
   
    public $typeArr = [
        1=>'个人',
        2=>'团队',
    ];
    
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? 0;
        $optype = $input['optype'] == 1 ? 1 : 0;
        $type = $input['type']==2 ? 2 : 1;
        
        $lockKey = 'SetCanWithdrawForm';
        $MyRedis = new MyRedis();
        $MyRedis->del_lock($lockKey);
        $lock = $MyRedis->setnx_lock($lockKey, 60);
        if(!$lock){
            return $this->response()->error('操作频繁');
        }
//         $this->radio('type','操作对象')->options($this->typeArr)->required();
//         $this->radio('optype','操作类型')->options([0=>'禁止',1=>'允许'])->required();
        $user = User::query()->where('id', $id)->first();
        
     
        
        if ($type==2) 
        {
            $user->team_can_withdraw = $optype;
            
            if($user->path) {
                $path = $user->path."{$user->id}-";
            } else {
                $path = "-{$user->id}-";
            }
            
            User::query()
                ->where('path', 'like', "{$path}%")
                ->update(['can_withdraw'=>$optype]);
        }
        
        $user->can_withdraw = $optype;
        $user->save();
        
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
        $this->display('id', '用户ID');
//         $this->display('code', '邀请码');
        $this->display('wallet', '用户地址');
        $this->display('is_withdraw', '个人提币');
        $this->display('team_can_withdraw', '团队提币');
        
        $this->radio('type','操作对象')->options($this->typeArr)->required()->help("选择团队则个人+团队一起设置");
        $this->radio('optype','操作类型')->options([1=>'允许', 0=>'禁止'])->required();
        $this->disableResetButton();
    }
    
    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $id = $this->payload['id'] ?? 0;
        
        $user = User::query()->where('id', $id)->first(['id','wallet','can_withdraw','team_can_withdraw','code']);
        
        return [
            'id' => $user->id,
            'wallet' => $user->wallet,
            'code' => $user->code,
            'is_withdraw' => $user->can_withdraw==1 ? '允许' : '禁止',
            'team_can_withdraw' => $user->team_can_withdraw==1 ? '允许' : '禁止',
//             'type' => 1,
        ];
    }
}
