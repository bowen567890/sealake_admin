<?php

namespace App\Admin\Controllers;

use App\Models\SuperNodeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SuperNodeOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SuperNodeOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('usdt_num');
            $grid->column('dogbee');
            $grid->column('small_yeji')->help('激活时小区业绩');
            //             $grid->column('pay_type');
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
        return Show::make($id, new SuperNodeOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('usdt_num');
            $show->field('dogbee');
            $show->field('small_yeji');
            $show->field('pay_type');
            $show->field('pay_status');
            $show->field('ordernum');
            $show->field('finish_time');
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
        return Form::make(new SuperNodeOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('usdt_num');
            $form->text('dogbee');
            $form->text('small_yeji');
            $form->text('pay_type');
            $form->text('pay_status');
            $form->text('ordernum');
            $form->text('finish_time');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
