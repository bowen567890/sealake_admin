<?php

namespace App\Admin\Controllers;

use App\Models\SignOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SignOrderController extends AdminController
{
    protected function grid()
    {
        return Grid::make(SignOrder::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('usdt_num');
            $grid->column('sign_power');
            $grid->column('dogbee');
            $grid->column('coin_price');
//             $grid->column('pay_type');
//             $grid->column('is_repeat');
            $grid->column('sign_price_rate');
//             $grid->column('ordernum');
            $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title('交易哈希');
                // 自定义图标
                return $this->hash;
            });
            $grid->column('created_at');
//             $grid->column('updated_at')->sortable();
        
            $grid->model()->orderBy('id','desc');
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new SignOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('usdt_num');
            $show->field('sign_power');
            $show->field('dogbee');
            $show->field('coin_price');
            $show->field('pay_type');
            $show->field('is_repeat');
            $show->field('sign_price_rate');
            $show->field('ordernum');
            $show->field('hash');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SignOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('usdt_num');
            $form->text('sign_power');
            $form->text('dogbee');
            $form->text('coin_price');
            $form->text('pay_type');
            $form->text('is_repeat');
            $form->text('sign_price_rate');
            $form->text('ordernum');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
