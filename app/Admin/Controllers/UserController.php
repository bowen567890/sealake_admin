<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\SetBalanceNum;
use App\Admin\Actions\Grid\UpdateWallet;
use App\Admin\Actions\Grid\SetManageRank;

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
    public $manageRankArr = [];
    public function __construct()
    {
        $rankArr = RankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
        $this->rankArr = array_merge([0=>'V0'], $rankArr);
        
        $this->manageRankArr = ManageRankConfig::query()->orderBy('lv', 'asc')->pluck('name', 'lv')->toArray();
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
            $grid->column('power');
            $grid->column('dogbee');
            $grid->column('point');
            
            $grid->column('rank')->using($this->rankArr)->label('success');
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
            $grid->column('is_node')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_node];
                $colour = $this->is_node == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('super_node')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->super_node];
                $colour = $this->super_node == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('is_branch')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_branch];
                $colour = $this->is_branch == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('manage_rank')->using($this->manageRankArr)->label('success');
            $grid->column('is_merchant')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_merchant];
                $colour = $this->is_merchant == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
          
            $grid->column('zhi_num');
            $grid->column('group_num');
            
            $grid->column('small_yeji','小区业绩')->display(function() {
                //小区质押数量
                $small_yeji = '0.00';
                if ($this->zhi_num<2) {
                    return '0.00';
                } else {
                    $id = UserModel::query()->where('parent_id', $this->id)->orderBy('total_yeji', 'desc')->value('id');
                    if (intval($id)>0)
                    {
                        $small_yeji = UserModel::query()
                            ->where('parent_id', $this->id)
                            ->where('id', '<>', $id)
                            ->sum('total_yeji');
                        $small_yeji = @bcadd($small_yeji, '0', 2);
                    }
                    return $small_yeji;
                }
            });
            
            $grid->column('lottery_num');
            $grid->column('last_sign_time');
            
//             $grid->column('achievement');
//             $grid->column('achievement_ma');
//             $grid->column('status','状态')->switch('',true);

            
//             $grid->column('pathlist', '关系树')->display('查看') // 设置按钮名称
//                 ->modal(function ($modal) {
//                     // 设置弹窗标题
//                     $modal->title('关系树');
//                     $path = $this->path;
//                     $parentIds = explode('-',trim($path,'-'));
//                     $parentIds = array_reverse($parentIds);
//                     $parentIds = array_filter($parentIds);
                    
//                     $html = '<table class="table custom-data-table data-table" id="grid-table">
//                                     <thead>
//                                     	  <tr>
//                                     			 <th>上级ID</th>
//                                                  <th>层级</th>
//                                                  <th>等级</th>
//                                     			 <th>地址</th>
//                                     	  </tr>
//                                     </thead>
//                                     <tbody>';
                    
//                     if ($parentIds)
//                     {
//                         $list = UserModel::query()->whereIn('id',$parentIds)->orderBy('level', 'desc')->get(['id','wallet','level','code','rank'])->toArray();
//                         if ($list) {
//                             foreach ($list as $val) {
//                                 $html.= "<tr><td>{$val['id']}</td>";
//                                 $html.= "<td>{$val['level']}</td>";
//                                 $html.= "<td>V{$val['rank']}</td>";
//                                 $html.= "<td>{$val['wallet']}</td>";
//                                 $html.= "</tr>";
//                             }
//                         }
//                     }
                    
//                     $html.= "</tbody></table>";
//                     // 自定义图标
//                     return $html;
//             });
            
            
            $grid->column('created_at','注册时间');
            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);
            
            //如果代发货，显示发货按钮
            $grid->actions(function (Grid\Displayers\Actions $actions) use (&$grid){
                $actions->append(new SetBalanceNum());
                $actions->append(new UpdateWallet());
                $actions->append(new SetManageRank());
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
                $filter->equal('wallet');
//                 $filter->equal('status','状态')->radio([0=>'禁用',1=>'有效']);
                $filter->equal('rank')->select($this->rankArr);
                $filter->equal('hold_rank')->select($this->holdRankArr);
                $filter->equal('is_node')->select($this->holdRankArr);
                $filter->equal('super_node')->select($this->holdRankArr);
                $filter->equal('is_branch')->select($this->holdRankArr);
                $filter->equal('is_merchant')->select($this->holdRankArr);
                $filter->equal('manage_rank')->select($this->manageRankArr);
                $filter->between('created_at','注册时间')->datetime();
            });
        });
    }


    protected function form()
    {
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            $form->display('wallet');
            
            $form->select('rank', '用户等级')->required()->options($this->rankArr)->default(0);
            $form->radio('hold_rank', '保持等级')->required()->options($this->holdRankArr)->default(0);
            $form->radio('is_node', '普通节点')->required()->options($this->holdRankArr)->default(0)->help('享受普通节点池分红');
            $form->radio('super_node', '超级节点')->required()->options($this->holdRankArr)->default(0)->help('享受超级节点池分红');
            $form->radio('is_branch')->required()->options($this->holdRankArr)->default(0)->help('享受报单金额收益');
            
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                if ($id==1) {
                    $form->is_branch = 1;
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
