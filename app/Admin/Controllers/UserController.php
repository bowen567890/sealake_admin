<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\SetBalanceNum;
use App\Admin\Actions\Grid\UpdateWallet;
use App\Admin\Actions\Grid\SetManageRank;
use App\Admin\Actions\Grid\SetCanWithdraw;
use App\Admin\Actions\Grid\SetWhiteWithdraw;

use App\Admin\Repositories\User;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

use App\Models\User as UserModel;
use App\Models\RankConfig;
use Dcat\Admin\Http\JsonResponse;
use App\Models\ManageRankConfig;

class UserController extends AdminController
{
    public $holdRankArr = [
        0 => '否',1 => '是'
    ];
    public $rankArr = [];
    public $nodeRankArr = [0=> '', 1=>'精英节点',2=>'核心节点',3=>'创世节点'];
    public function __construct()
    {
        $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->rankArr = array_merge([0=>'V0'], $rankArr);
    }
    
    protected function grid()
    {
        return Grid::make(User::with(['parent']), function (Grid $grid) {
            
            $grid->column('id');
            $grid->column('wallet');
//             $grid->column('wallet')->display('点击查看') // 设置按钮名称
//             ->modal(function ($modal) {
//                 // 设置弹窗标题
//                 $modal->title('钱包地址');
//                 // 自定义图标
//                 return $this->wallet;
//             });
            $grid->column('parent.id','上级ID');
            $grid->column('code');
            $grid->column('usdt');
            
            $grid->column('rank', '团队等级')->using($this->rankArr)->label('success');
            $grid->column('hold_rank')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->hold_rank];
                $colour = $this->hold_rank == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
          
            $grid->column('node_rank', '节点等级')->using($this->nodeRankArr)->label('success');
          
            $grid->column('is_active', '活跃用户')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_active];
                $colour = $this->is_active == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            })->help('未赎回保证金为活跃用户');
            
            
            
            $grid->column('can_withdraw', '个人提币')
            ->display(function () {
                $arr = [0=>'禁止',1=>'允许'];
                $msg = $arr[$this->can_withdraw];
                $colour = $this->can_withdraw == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('team_can_withdraw', '团队提币')
            ->display(function () {
                $arr = [0=>'禁止',1=>'允许'];
                $msg = $arr[$this->team_can_withdraw];
                $colour = $this->team_can_withdraw == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            
            $grid->column('is_white_withdraw')
            ->display(function () {
                $arr = [0=>'否',1=>'是'];
                $msg = $arr[$this->is_white_withdraw];
                $colour = $this->is_white_withdraw == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            })->help('设置白名单不受提币设置配置限制,可自由提币');
            
            $grid->column('is_white_withdraw_team')
            ->display(function () {
                $arr = [0=>'否',1=>'是'];
                $msg = $arr[$this->is_white_withdraw_team];
                $colour = $this->is_white_withdraw_team == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            })->help('设置白名单不受提币设置配置限制,可自由提币');
            
            $grid->column('tuijian','团队')->display(function (){
                $html = "";
                $html .= "<div style='margin-top: 2px;'>直推人数：" . $this->zhi_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>团队人数：" . $this->group_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>直推有效：" . $this->zhi_valid . "</div>";
                return $html;
            });
            
            $grid->column('yeji','业绩')->display(function (){
                
                $big_num = UserModel::query()->where('parent_id', $this->id)->orderBy('total_num', 'desc')->value('total_num');
                $big_num = intval($big_num);
                
                $html = "";
                $html .= "<div style='margin-top: 2px;'>个人单数：" . $this->self_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>团队单数：" . $this->team_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>大区单数：" . $big_num . "</div>";
                $html .= "<div style='margin-top: 2px;'>小区单数：" . $this->small_num . "</div>";
                return $html;
            });
//             $grid->column('achievement');
//             $grid->column('achievement_ma');
//             $grid->column('status','状态')->switch('',true);

            
            $grid->column('pathlist', '关系树')->display('查看') // 设置按钮名称
                ->modal(function ($modal) {
                    // 设置弹窗标题
                    $modal->title('关系树');
                    $path = $this->path;
                    $parentIds = explode('-',trim($path,'-'));
                    $parentIds = array_reverse($parentIds);
                    $parentIds = array_filter($parentIds);
                    
                    $html = '<table class="table custom-data-table data-table" id="grid-table">
                                    <thead>
                                    	  <tr>
                                    			 <th>上级ID</th>
                                                 <th>层级</th>
                                    			 <th>地址</th>
                                                 <th>个人提币</th>
                                    			 <th>团队提币</th>
                                                 <th>个人提币白名单</th>
                                    			 <th>团队提币白名单</th>
                                    	  </tr>
                                    </thead>
                                    <tbody>';
                    
                    if ($parentIds)
                    {
                        $list = UserModel::query()
                            ->whereIn('id',$parentIds)
                            ->orderBy('level', 'desc')->get(['id','wallet','level','code','rank','can_withdraw','team_can_withdraw','is_white_withdraw','is_white_withdraw_team'])
                            ->toArray();
                        if ($list) 
                        {
                            
                            
                            foreach ($list as $val) 
                            {
                                if ($val['team_can_withdraw']==1) {
                                    $team_can_withdraw = "<span class='label' style='background:#4277cf'>允许</span>";
                                } else {
                                    $team_can_withdraw = "<span class='label' style='background:gray'>禁止</span>";
                                }
                                if ($val['can_withdraw']==1) {
                                    $can_withdraw = "<span class='label' style='background:#4277cf'>允许</span>";
                                } else {
                                    $can_withdraw = "<span class='label' style='background:gray'>禁止</span>";
                                }
                                
                                if ($val['is_white_withdraw']==1) {
                                    $is_white_withdraw = "<span class='label' style='background:#4277cf'>否</span>";
                                } else {
                                    $is_white_withdraw = "<span class='label' style='background:gray'>是</span>";
                                }
                                if ($val['is_white_withdraw_team']==1) {
                                    $is_white_withdraw_team = "<span class='label' style='background:#4277cf'>否</span>";
                                } else {
                                    $is_white_withdraw_team = "<span class='label' style='background:gray'>是</span>";
                                }
                                
                                $html.= "<tr><td>{$val['id']}</td>";
                                $html.= "<td>{$val['level']}</td>";
                                $html.= "<td>{$val['wallet']}</td>";
                                $html.= "<td>{$can_withdraw}</td>";
                                $html.= "<td>{$team_can_withdraw}</td>";
                                $html.= "<td>{$is_white_withdraw}</td>";
                                $html.= "<td>{$is_white_withdraw_team}</td>";
                                $html.= "</tr>";
                            }
                        }
                    }
                    
                    $html.= "</tbody></table>";
                    // 自定义图标
                    return $html;
            });
            
            
            $grid->column('created_at','注册时间');
            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);
            
            
            $titles = [
                'id' => '用户ID',
                'wallet' => '钱包地址',
                'code' => '邀请码',
                'parent.id' => '上级ID',
                'rank' => '团队等级',
                'usdt' => 'USDT',
                'hold_rank' => '保持等级',
                'node_rank' => '节点等级',
                'is_active' => '活跃用户',
                'is_valid' => '有效用户',
                'zhi_valid' => '直推有效用户',
                'zhi_num' => '直推人数',
                'group_num' => '团队人数',
                'self_num' => '个人单数',
                'team_num' => '团队单数',
                'small_num' => '小区单数',
                'can_withdraw' => '个人提币',
                'team_can_withdraw' => '团队提币',
                'is_white_withdraw' => '个人提币白名单',
                'is_white_withdraw_team' => '团队提币白名单',
                'created_at' => '注册时间',
            ];
            
            
            
            $grid->export($titles)->rows(function ($rows) {
                set_time_limit(0);
                ini_set('memory_limit','1024M');
                
//                 public $holdRankArr = [
//                     0 => '否',1 => '是'
//                 ];
//                 public $rankArr = [];
//                 public $nodeRankArr = [0=> '', 1=>'精英节点',2=>'核心节点',3=>'创世节点'];
//                 public function __construct()
//                 {
//                     $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
//                     $this->rankArr = array_merge([0=>'V0'], $rankArr);
//                 }
                
                $rankArr = $this->rankArr;
                $nodeRankArr = $this->nodeRankArr;
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                
                foreach ($rows as $index => &$row)
                {
                    $row['rank'] = $rankArr[$row['rank']];
                    $row['hold_rank'] = $arr[$row['hold_rank']];
                    $row['node_rank'] = $nodeRankArr[$row['node_rank']];
                    
                    $row['can_withdraw'] = $arr[$row['can_withdraw']];
                    $row['team_can_withdraw'] = $arr[$row['team_can_withdraw']];
                    
                    $row['is_white_withdraw'] = $arr[$row['is_white_withdraw']];
                    $row['is_white_withdraw_team'] = $arr[$row['is_white_withdraw_team']];
                }
                return $rows;
            });
            
            //如果代发货，显示发货按钮
            $grid->actions(function (Grid\Displayers\Actions $actions) use (&$grid){
                $actions->append(new SetBalanceNum());
                $actions->append(new UpdateWallet());
                $actions->append(new SetCanWithdraw());
                $actions->append(new SetWhiteWithdraw());
//                 $actions->append(new SetManageRank());
            });
            
            $grid->disableRowSelector();
//             $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->disableDeleteButton();
            $grid->disableCreateButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('parent.id','上级ID');
                $filter->equal('wallet');
                $filter->equal('parent.wallet','上级地址');
//                 $filter->equal('status','状态')->radio([0=>'禁用',1=>'有效']);
                $filter->equal('rank', '团队等级')->select($this->rankArr);
                $filter->equal('hold_rank')->select($this->holdRankArr);
                $filter->equal('node_rank', '节点等级')->select($this->nodeRankArr);
                $filter->equal('is_active', '活跃用户')->select($this->holdRankArr);
                $filter->equal('can_withdraw', '个人提币')->select([0=>'禁止',1=>'允许']);
                $filter->equal('team_can_withdraw', '团队提币')->select([0=>'禁止',1=>'允许']);
                
                $filter->equal('is_white_withdraw')->select([0=>'否',1=>'是']);
                $filter->equal('is_white_withdraw_team')->select([0=>'否',1=>'是']);
                
                $filter->between('created_at','注册时间')->datetime();
            });
        });
    }


    protected function form()
    {
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            $form->display('wallet');
            
            $form->select('rank', '团队等级')->required()->options($this->rankArr)->default(0);
            $form->radio('hold_rank', '保持等级')->required()->options($this->holdRankArr)->default(0);
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                if ($id==1) {
//                     $form->is_branch = 1;
                }
            });
            
            $form->disableViewButton();
            $form->disableDeleteButton();
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
    
    /**
     * 删除
     */
    public function destroy($id)
    {
        return JsonResponse::make()->success('删除成功')->location('users');
    }

}
