<?php

namespace App\Admin\Controllers;

use App\Models\MerchantOrderLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class MerchantOrderLogController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MerchantOrderLog(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('point');
            $grid->column('usdt_num');
            $grid->column('dogbee');
            $grid->column('pay_type');
            $grid->column('pay_status');
            $grid->column('ordernum');
            $grid->column('finish_time');
            $grid->column('hash');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
        return Show::make($id, new MerchantOrderLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('point');
            $show->field('usdt_num');
            $show->field('dogbee');
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
        return Form::make(new MerchantOrderLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('point');
            $form->text('usdt_num');
            $form->text('dogbee');
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
