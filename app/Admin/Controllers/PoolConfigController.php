<?php

namespace App\Admin\Controllers;

use App\Models\PoolConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PoolConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PoolConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('type');
            $grid->column('pool');
            $grid->column('rate');
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
        return Show::make($id, new PoolConfig(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('pool');
            $show->field('rate');
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
        return Form::make(new PoolConfig(), function (Form $form) {
            $form->display('id');
            $form->text('type');
            $form->text('pool');
            $form->text('rate');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
