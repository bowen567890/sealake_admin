<?php

namespace App\Admin\Controllers;

use App\Models\ManageOperateLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ManageOperateLogController extends AdminController
{
    protected function grid()
    {
        return Grid::make(ManageOperateLog::with(['user', 'targetuser']), function (Grid $grid) {
            $grid->column('id');
            $grid->column('user_id');
            $grid->column('user.wallet', '用户地址');
            $grid->column('target_id');
            $grid->column('targetuser.wallet', '目标地址');
            $grid->column('old_rank')->display(function($old_rank) {
                return "S{$old_rank}";
            });
            $grid->column('new_rank')->display(function($new_rank) {
                return "S{$new_rank}";
            });
            $grid->column('is_backend')
            ->display(function () {
                $arr = [
                    0 => '否',
                    1 => '是',
                ];
                $msg = $arr[$this->is_backend];
                $colour = $this->is_backend == 1 ? '#4277cf' : 'gray';
                return "<span class='label' style='background:{$colour}'>{$msg}</span>";
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
                $filter->equal('target_id');
                $filter->equal('is_backend')->select([
                    0 => '否',
                    1 => '是',
                ]);
            });
        });
    }
}
