<?php

namespace App\Admin\Controllers;

use App\Models\NormalNodeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class NormalNodeOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new NormalNodeOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('usdt_num');
            $grid->column('dogbee');
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
        return Show::make($id, new NormalNodeOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('usdt_num');
            $show->field('dogbee');
            $show->field('pay_type');
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
        return Form::make(new NormalNodeOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('usdt_num');
            $form->text('dogbee');
            $form->text('pay_type');
            $form->text('ordernum');
            $form->text('finish_time');
            $form->text('hash');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
