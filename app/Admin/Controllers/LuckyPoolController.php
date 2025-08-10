<?php

namespace App\Admin\Controllers;

use App\Models\LuckyPool;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Http\JsonResponse;

class LuckyPoolController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new LuckyPool(), function (Grid $grid) {
            $grid->column('pool');
            $grid->column('w_rate');
            $grid->column('push_usdt');
            $grid->column('sign_usdt');
            $grid->column('random_min');
            $grid->column('random_max');
            $grid->column('updated_at')->sortable();
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
        });
    }

    protected function form()
    {
        return Form::make(new LuckyPool(), function (Form $form) {
            $form->display('pool');
            $form->decimal('w_rate')->required()->help('个人提取DOGBEE，按此比率进入抽奖池(0.1=10%)');
            $form->number('push_usdt')->min(1)->default(1)->required()->help('个人推广业绩每累计此数(USDT),获得一次抽奖次数');
            $form->number('sign_usdt')->min(1)->default(1)->required()->help('个人签到每累计此数(USDT),获得一次抽奖次数');
            $form->decimal('random_min')->required()->help('抽取幸运池比率下限(0.1=10%)');
            $form->decimal('random_max')->required()->help('抽取幸运池比率上限(0.1=10%)');
        
            $form->saving(function (Form $form)
            {
                $id = $form->getKey();
                $w_rate = @bcadd($form->w_rate, '0', 2);
                if (bccomp($w_rate, '1', 2)>0 || bccomp('0', $w_rate, 2)>0) {
                    return $form->response()->error('提币入池比率不正确');
                }
                $random_min = @bcadd($form->random_min, '0', 4);
                if (bccomp($random_min, '1', 4)>0 || bccomp('0', $random_min, 4)>0) {
                    return $form->response()->error('抽取比率下限比率不正确');
                }
                $random_max = @bcadd($form->random_max, '0', 4);
                if (bccomp($random_max, '1', 4)>0 || bccomp('0', $random_max, 4)>0) {
                    return $form->response()->error('抽取比率上限比率不正确');
                }
                if (bccomp($random_min, $random_max, 4)>0) {
                    return $form->response()->error('抽取比率比率不正确');
                }
                
                $form->w_rate = $w_rate;
                $form->random_min = $random_min;
                $form->random_max = $random_max;
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
        return JsonResponse::make()->success('删除成功')->location('lucky_pool');
    }
}
