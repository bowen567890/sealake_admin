<?php

namespace App\Admin\Controllers;

use App\Models\DepthConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class DepthConfigController extends AdminController
{
    public $needArr = [
        0 => '否',
        1 => '是',
    ];
    protected function grid()
    {
        return Grid::make(new DepthConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('depth');
//             $grid->column('need_valid')
//             ->display(function () {
//                 $arr = [
//                     0 => '否',
//                     1 => '是',
//                 ];
//                 $msg = $arr[$this->need_valid];
//                 $colour = $this->need_valid == 1 ? '#4277cf' : 'gray';
//                 return "<span class='label' style='background:{$colour}'>{$msg}</span>";
//             });
//             $grid->column('push_num');
            $grid->column('rate');
            $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('depth','asc');
            
//             $grid->disableCreateButton();
            $grid->disableViewButton();
            $grid->disableRowSelector();
//             $grid->disableDeleteButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    protected function form()
    {
        return Form::make(new DepthConfig(), function (Form $form) {
            $form->display('id');
            $form->number('depth')->default(1)->min(1)->required();
//             $form->radio('need_valid')->required()->options($this->needArr)->default(0)->help('个人是有效用户(合成能源石)');
//             $form->number('push_num')->default(0)->min(0)->required();
            $form->decimal('rate')->placeholder('0.1=10%')->help('加速比率(0.1=10%)')->required();
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $rate = @bcadd($form->rate, '0', 2);
                if (bccomp($rate, '1', 2)>0 || bccomp('0', $rate, 2)>0) {
                    return $form->response()->error('加速比率不正确');
                }
                
                $depth = intval($form->depth);
                if ($form->isCreating()) {
                    // 也可以这样获取自增ID
                    $res = DepthConfig::query()->where('depth', $depth)->first();
                    if ($res) {
                        return $form->response()->error('层级已存在');
                    }
                }
                if ($form->isEditing()) {
                    $res = DepthConfig::query()->where('depth', $depth)->first();
                    if ($res) {
                        if ($res->id!=$id){
                            return $form->response()->error('层级已存在');
                        }
                    }
                }
                
                $form->rate = $rate;
                $form->depth = $depth;
            });
            
            $form->saved(function (Form $form, $result) {
                DepthConfig::SetListCache();
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
        $res = DepthConfig::query()->where('id', $id)->first();
        $descDepth = DepthConfig::query()->orderBy('depth', 'desc')->first();
        if ($descDepth && $descDepth->id!=$res->id) {
            return JsonResponse::make()->error('只能从最层级开始删除')->location('depth_config');
        }
        DepthConfig::query()->where('id', $id)->delete();
        DepthConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('depth_config');
    }
}
