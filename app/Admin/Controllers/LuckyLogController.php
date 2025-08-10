<?php

namespace App\Admin\Controllers;

use App\Models\LuckyLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class LuckyLogController extends AdminController
{
    
    protected function grid()
    {
        return Grid::make(LuckyLog::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('rate');
            $grid->column('num');
//             $grid->column('ordernum');
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
                $filter->equal('user.wallet', '用户地址');
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
        return Show::make($id, new LuckyLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('rate');
            $show->field('num');
            $show->field('ordernum');
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
        return Form::make(new LuckyLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('rate');
            $form->text('num');
            $form->text('ordernum');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
