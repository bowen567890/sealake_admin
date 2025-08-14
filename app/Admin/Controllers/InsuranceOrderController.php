<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class InsuranceOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InsuranceOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('ticket_id');
            $grid->column('user_ticket_id');
            $grid->column('status');
            $grid->column('is_redeem');
            $grid->column('insurance');
            $grid->column('ticket_price');
            $grid->column('multiple');
            $grid->column('total_income');
            $grid->column('wait_income');
            $grid->column('over_income');
            $grid->column('next_time');
            $grid->column('redeem_time');
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
        return Show::make($id, new InsuranceOrder(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('ticket_id');
            $show->field('user_ticket_id');
            $show->field('status');
            $show->field('is_redeem');
            $show->field('insurance');
            $show->field('ticket_price');
            $show->field('multiple');
            $show->field('total_income');
            $show->field('wait_income');
            $show->field('over_income');
            $show->field('next_time');
            $show->field('redeem_time');
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
        return Form::make(new InsuranceOrder(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('ticket_id');
            $form->text('user_ticket_id');
            $form->text('status');
            $form->text('is_redeem');
            $form->text('insurance');
            $form->text('ticket_price');
            $form->text('multiple');
            $form->text('total_income');
            $form->text('wait_income');
            $form->text('over_income');
            $form->text('next_time');
            $form->text('redeem_time');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
