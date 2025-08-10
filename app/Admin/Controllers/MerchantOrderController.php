<?php

namespace App\Admin\Controllers;

use App\Models\MerchantOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class MerchantOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MerchantOrder(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('point');
            $grid->column('usdt_num');
            $grid->column('dogbee');
//             $grid->column('pay_type');
            $grid->column('ordernum');
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
}
