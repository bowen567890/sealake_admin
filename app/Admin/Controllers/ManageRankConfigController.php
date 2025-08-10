<?php

namespace App\Admin\Controllers;

use App\Models\ManageRankConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class ManageRankConfigController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new ManageRankConfig(), function (Grid $grid) {
            //             $grid->column('id')->sortable();
            $grid->column('lv')->display(function() {
                return "<span class='label' style='background:#21b978'>S{$this->lv}</span>";
            });
            $grid->column('reward_usdt');
            
            $grid->column('is_show', '展示操作')->help('用户端显示和操作')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_show];
                $colour = $this->is_show == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            
            $grid->column('backend_set')->help('只允许后台设置')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->backend_set];
                $colour = $this->backend_set == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            //             $grid->column('created_at');
//             $grid->column('updated_at')->sortable();
            
            $grid->model()->orderBy('lv','asc');
            
            
            $grid->disableRowSelector();
            //             $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->disableDeleteButton();
            $grid->disableCreateButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    protected function form()
    {
        return Form::make(new ManageRankConfig(), function (Form $form) {
            //             $form->display('id');
            $form->display('name');
//             $form->number('lv')->default(0)->min(0)->required()->help('输入数字几就是S几(例输入1=S1)');
            $form->decimal('reward_usdt')->default(0)->required()->help('投资金额×此比率级差(0.1=10%)');
            
            $form->radio('is_show', '展示操作')->required()->options(
                [
                    0 => '否',
                    1 => '是',
                ])->default(0)->help('用户端显示和操作');
                
            
            $form->radio('backend_set')->required()->options(
                [
                    0 => '否',
                    1 => '是',
                ])->default(0)->help('只允许后台设置');
                
                
                $form->saving(function (Form $form)
                {
                    $id = $form->getKey();
                    $rate = @bcadd($form->reward_usdt, '0', 2);
                    if (bccomp($rate, '1', 2)>0 || bccomp('0', $rate, 2)>0) {
                        return $form->response()->error('奖励比率不正确');
                    }
                        
//                     $lv = intval($form->lv);
//                     if ($form->isCreating()) {
//                         // 也可以这样获取自增ID
//                         $res = ManageRankConfig::query()->where('lv', $lv)->first();
//                         if ($res) {
//                             return $form->response()->error('等级已存在');
//                         }
//                     }
//                     if ($form->isEditing()) {
//                         $res = ManageRankConfig::query()->where('lv', $lv)->first();
//                         if ($res) {
//                             if ($res->id!=$id){
//                                 return $form->response()->error('等级已存在');
//                             }
//                         }
//                     }
//                     $form->lv = $lv;
//                     $form->name = "S{$lv}";
                });
            
            $form->saved(function (Form $form, $result) {
                ManageRankConfig::SetListCache();
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
        $res = ManageRankConfig::query()->where('id', $id)->first();
        $isExist = User::query()->where('manage_rank', $res->lv)->exists();
        if ($isExist) {
            return JsonResponse::make()->error('删除失败,用户存在此等级')->location('manage_config');
        }
        $descRank = ManageRankConfig::query()->orderBy('lv', 'desc')->first();
        if ($descRank && $descRank->id!=$res->id) {
            return JsonResponse::make()->error('只能从最高级开始删除')->location('manage_config');
        }
        
        ManageRankConfig::query()->where('id', $id)->delete();
        ManageRankConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('manage_config');
    }
}
