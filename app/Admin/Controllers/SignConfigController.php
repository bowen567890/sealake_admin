<?php

namespace App\Admin\Controllers;

use App\Models\SignConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class SignConfigController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new SignConfig(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('price')->help('签到价格(USDT)');
            $grid->column('sign_power_rate')->help('签到释放算力比率(0.1=10%)');
            $grid->column('sort')->help('越小排在越前面');
//             $grid->column('updated_at');
            
            $grid->model()->orderBy('sort','asc');
            $grid->model()->orderBy('price','asc');
            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);
            
            $grid->disableRowSelector();		//帅选按钮
            $grid->disableViewButton();			//查看按钮
            $grid->disableRowSelector();		//帅选按钮
            
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    protected function form()
    {
        return Form::make(new SignConfig(), function (Form $form) {
            $form->display('id');
            $form->decimal('price')->required()->help('签到价格(USDT)');
            $form->decimal('sign_power_rate')->required()->help('签到释放算力比率(0.1=10%)');
            $form->number('sort')->default(99)->required()->help('越小排在越前面');
            
            $form->saving(function (Form $form) 
            {
                $post = $_POST;
                if ($post && isset($post['price']))
                {
                    $price = @bcadd($form->price, '0', 2);
                    
                    if (bccomp($price, '0', 2)<0) {
                        return JsonResponse::make()->error('签到价格不正确');
                    }
                    $sign_power_rate = @bcadd($form->sign_power_rate, '0', 4);
                    if (bccomp($sign_power_rate, '0', 4)<=0) {
                        return JsonResponse::make()->error('签到算力比率不正确');
                    }
                    $sign_power_rate = bccomp($sign_power_rate, '1', 4)>=0 ? '1' : $sign_power_rate;
                    
                    $id = $form->getKey();
                    $res = SignConfig::query()->where('price', $price)->where('is_del', 0)->first();
                    if ($res)
                    {
                        if (!$id) {
                            return JsonResponse::make()->error('签到价格已存在');
                        }
                        if ($id && $res->id!=$id) {
                            return JsonResponse::make()->error('签到价格已存在');
                        }
                    }
                    $form->price = $price;
                    $form->sign_power_rate = $sign_power_rate;
                }
            });
                
            $form->saved(function (Form $form, $result) {
                SignConfig::SetListCache();
                SignConfig::SetAllListCache();
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
        SignConfig::query()->where('id', $id)->update(['is_del'=>1]);
        SignConfig::SetListCache();
        SignConfig::SetAllListCache();
        return JsonResponse::make()->success('删除成功')->location('sign_config');
    }
}
