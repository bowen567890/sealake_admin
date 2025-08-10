<?php

namespace App\Admin\Controllers;

use App\Models\PointConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class PointConfigController extends AdminController
{

    protected function grid()
    {
        return Grid::make(new PointConfig(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('usdt_num');
            $grid->column('point');
//             $grid->column('created_at');
            $grid->column('updated_at')->sortable();
            
            $grid->model()->where('is_del', '=', 0);
            $grid->model()->orderBy('usdt_num', 'asc')->orderBy('point', 'asc');
            
            $grid->disableViewButton();
            $grid->disableRowSelector();
            //             $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new PointConfig(), function (Form $form) {
            $form->decimal('usdt_num')->required()->help('支付此价值USDT的DOGBEE购买积分');
            $form->number('point')->min(1)->default(1)->required();
            
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $usdt_num = @bcadd($form->usdt_num, '0', 2);
                if (bccomp('0', $usdt_num, 2)>=0) {
                    return $form->response()->error('支付价格不正确');
                }
                
                if ($form->isCreating()) {
                    // 也可以这样获取自增ID
                    $res = PointConfig::query()->where('usdt_num', $usdt_num)->first();
                    if ($res) {
                        return $form->response()->error('此价格已存在');
                    }
                }
                if ($form->isEditing()) {
                    $res = PointConfig::query()->where('usdt_num', $usdt_num)->first();
                    if ($res) {
                        if ($res->id!=$id){
                            return $form->response()->error('此价格已存在');
                        }
                    }
                }
                $form->usdt_num = $usdt_num;
            });
            
            $form->saved(function (Form $form, $result) {
                PointConfig::SetListCache();
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
        PointConfig::query()->where('id', $id)->update(['is_del'=>1]);
        PointConfig::SetListCache();
        return JsonResponse::make()->success('删除成功')->location('point_config');
    }
}
