<?php

namespace App\Admin\Controllers;

use App\Models\SeeConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class SeeConfigController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new SeeConfig(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('num');
            $grid->column('min_depth');
            $grid->column('max_depth');
            $grid->column('rate');
//             $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('num','asc');
            
//             $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableViewButton();
//             $grid->disableDeleteButton();
//             $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('num');
                $filter->equal('depth');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SeeConfig(), function (Form $form) {
            $form->display('id');
            $form->number('num')->min(1)->default(1)->required()->help('推荐有效用户人数');
            $form->number('min_depth')->min(1)->default(1)->required();
            $form->number('max_depth')->min(1)->default(1)->required();
            $form->decimal('rate', '加速比率')->required()->placeholder('0.1=10%')->help('加速比率*(0.1=10%)')->required();
        
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $rate = @bcadd($form->rate, '0', 2);
                if (bccomp($rate, '1', 2)>0 || bccomp('0', $rate, 2)>=0) {
                    return $form->response()->error('加速比率不正确');
                }
                
                $num = intval($form->num);
                $min_depth = intval($form->min_depth);
                $max_depth = intval($form->max_depth);
                
                if ($min_depth>$max_depth) {
                    return $form->response()->error('层级配置不正确');
                }
                
                if ($form->isCreating()) {
                    // 也可以这样获取自增ID
                    $res = SeeConfig::query()->where('num', $num)->first();
                    if ($res) {
                        return $form->response()->error('配置已存在');
                    }
                }
                if ($form->isEditing()) {
                    $res = SeeConfig::query()->where('num', $num)->first();
                    if ($res) {
                        if ($res->id!=$id){
                            return $form->response()->error('配置已存在');
                        }
                    }
                }
                $form->rate = $rate;
            });
            
            $form->saved(function (Form $form, $result) {
                SeeConfig::SetListCache();
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
        SeeConfig::query()->where('id', $id)->delete();
        SeeConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('see_config');
    }
}
