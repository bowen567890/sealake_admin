<?php

namespace App\Admin\Controllers;

use App\Models\PowerOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PowerOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(PowerOrder::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('power');
            $grid->column('usdt_num');
//             $grid->column('pay_type');
//             $grid->column('ordernum');
            $grid->column('hash', '哈希')->display('点击查看') // 设置按钮名称
            ->modal(function ($modal) {
                // 设置弹窗标题
                $modal->title('交易哈希');
                // 自定义图标
                return $this->hash;
            });
//             $grid->column('is_sync');
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
        return Show::make($id, new PowerOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('power');
            $show->field('usdt_num');
            $show->field('pay_type');
            $show->field('ordernum');
            $show->field('hash');
            $show->field('is_sync');
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
        return Form::make(new PowerOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('power');
            $form->text('usdt_num');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('hash');
            $form->text('is_sync');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
