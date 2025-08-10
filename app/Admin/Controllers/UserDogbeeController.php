<?php

namespace App\Admin\Controllers;

use App\Models\UserDogbee;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserDogbeeController extends AdminController
{
    public $cateArr = [
        1=>'链上增加',
        2=>'链上扣除',
        3=>'余额提币',
        4=>'提币驳回',
        5=>'签到获得',
        6=>'推荐加速',
        7=>'见点加速',
        8=>'团队加速',
        9=>'幸运抽奖',
    ];
    protected function grid()
    {
        return Grid::make(new UserDogbee(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('type')
            ->display(function () {
                $arr = [1=>'收入', 2=>'支出'];
                $msg = $arr[$this->type];
                $colour = $this->type == 1 ? '#21b978' : '#ea5455';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
            });
            $grid->column('total');
            //             $grid->column('ordernum');
            //             $grid->column('msg');
            $grid->column('cate')->using($this->cateArr)->label();
            $grid->column('from_user_id');
            //             $grid->column('ma_usdt_price');
            $grid->column('content');
            $grid->column('created_at');
            $grid->model()->orderBy('id','desc');
            
            $grid->disableCreateButton();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableActions();
            $grid->scrollbarX();    			//滚动条
            $grid->paginate(10);				//分页
            
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id');
                $filter->equal('type')->select([1=>'收入', 2=>'支出']);
                $filter->equal('cate')->select($this->cateArr);
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
        return Show::make($id, new UserDogbee(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('from_user_id');
            $show->field('type');
            $show->field('total');
            $show->field('ordernum');
            $show->field('msg');
            $show->field('cate');
            $show->field('content');
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
        return Form::make(new UserDogbee(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('from_user_id');
            $form->text('type');
            $form->text('total');
            $form->text('ordernum');
            $form->text('msg');
            $form->text('cate');
            $form->text('content');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
