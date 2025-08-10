<?php

namespace App\Admin\Controllers;

use App\Models\UserPower;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserPowerController extends AdminController
{
    public $cateArr = [
        1=>'链上增加',
        2=>'链上扣除',
        3=>'注册赠送',
        4=>'购买算力',
        5=>'签到扣除',
        6=>'推荐加速',
        7=>'见点加速',
        8=>'团队加速',
        9=>'积分兑换',
        10=>'提币扣除',
        11=>'提币驳回',
    ];
    protected function grid()
    {
        return Grid::make(new UserPower(), function (Grid $grid) {
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
        return Show::make($id, new UserPower(), function (Show $show) {
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
        return Form::make(new UserPower(), function (Form $form) {
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
