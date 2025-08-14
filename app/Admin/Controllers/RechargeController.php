<?php

namespace App\Admin\Controllers;

use App\Models\Recharge;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RechargeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Recharge(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('main_chain');
            $grid->column('coin_type');
            $grid->column('num');
            $grid->column('ordernum');
            $grid->column('hash');
            $grid->column('date');
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
        return Show::make($id, new Recharge(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('main_chain');
            $show->field('coin_type');
            $show->field('num');
            $show->field('ordernum');
            $show->field('hash');
            $show->field('date');
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
        return Form::make(new Recharge(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('main_chain');
            $form->text('coin_type');
            $form->text('num');
            $form->text('ordernum');
            $form->text('hash');
            $form->text('date');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
