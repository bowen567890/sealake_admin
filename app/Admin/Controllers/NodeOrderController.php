<?php

namespace App\Admin\Controllers;

use App\Models\NodeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class NodeOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(NodeOrder::with(['ticket','rank']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('price');
            $grid->column('gift_ticket_id')->display(function() {
                if ($this->ticket){
                    return $this->ticket->ticket_price;
                }else{
                    return '0';
                }
            });
            $grid->column('gift_ticket_num');
            $grid->column('gift_rank_id')->display(function() {
                if ($this->rank){
                    return 'V'.$this->rank->lv;
                }else{
                    return 'V0';
                }
            });
            
            $grid->column('static_rate');
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
