<?php

namespace App\Admin\Controllers;

use App\Models\UserRankingDay;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserRankingDayController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserRankingDay(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('day');
            $grid->column('num');
//             $grid->column('total');
            $grid->column('ranking');
            $grid->column('reward');
//             $grid->column('created_at');
            $grid->column('updated_at')->sortable();
            
            $grid->model()->orderBy('id','desc');
            //             $grid->model()->orderBy('date','desc');
            //             $grid->model()->orderBy('total','desc');
        
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
//                 $filter->equal('user.account', '用户账号');
                $filter->date('day');
            });
        });
    }

   
}
