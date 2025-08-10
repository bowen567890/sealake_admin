<?php
namespace App\Admin\Controllers;

use App\Admin\Renderable\UserPowerTable;
use App\Admin\Repositories\User;
use App\Models\LevelConfig;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Tab;
use Dcat\Admin\Widgets\Card;
use App\Models\User as UserModel;

class UserTreeController extends AdminController
{
    public $rankArr = [
        0 => 'V0',1 => 'V1',2 => 'V2',3 => 'V3',4 => 'V4',5 => 'V5',6 => 'V6',7 => 'V7',
    ];
    
    public function index(Content $content)
    {
        return $content->header('推荐树')->description('推荐树管理')->body($this->grid());
    }

    protected function grid(){
        return Grid::make(User::with([]), function (Grid $grid) {
//            $grid->number();
            $grid->column('wallet')->tree();
            $grid->column('id','ID');
            $grid->column('rank')->using($this->rankArr)->label('success');
            $grid->column('code');
            $grid->column('usdt');
            $grid->column('power');
            $grid->column('dogbee');
            $grid->column('point');
            $grid->column('zhi_num');
            $grid->column('group_num');
            $grid->column('self_yeji');
            $grid->column('team_yeji');
            $grid->column('small_yeji','小区业绩')->display(function() {
                //小区质押数量
                $small_yeji = '0.00';
                if ($this->zhi_num<2) {
                    return '0.00';
                } else {
                    $id = UserModel::query()->where('parent_id', $this->id)->orderBy('total_yeji', 'desc')->value('id');
                    if (intval($id)>0)
                    {
                        $small_yeji = UserModel::query()
                        ->where('parent_id', $this->id)
                        ->where('id', '<>', $id)
                        ->sum('total_yeji');
                        $small_yeji = @bcadd($small_yeji, '0', 2);
                    }
                    return $small_yeji;
                }
            });
            
            /* 
            $grid->column('wallet', '钱包地址')->display('点击查看') // 设置按钮名称
                ->modal(function ($modal) {
                    // 设置弹窗标题
//                     $modal->title('钱包地址');
                    // 自定义图标
                    return $this->wallet;
            });
              */   
//             $grid->column('wallet', '钱包地址')
//             ->display('点击查看') // 设置按钮名称
//             ->expand(function () {
//                 // 返回显示的详情
//                 // 这里返回 content 字段内容，并用 Card 包裹起来
//                 $card = new Card(null, $this->wallet);
//                 return "<div style='padding:10px 10px 0'>$card</div>";
//             });
            
//             $grid->column('status','状态')->switch('',true);
//             $grid->column('created_at','注册时间');
            $grid->disableRowSelector();

            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->model()->orderBy('id','desc');
            $grid->model()->where('is_del', '=', 0);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id','用户ID');
                $filter->equal('wallet','钱包地址');
//                 $filter->equal('status','状态')->radio([0=>'禁用',1=>'有效']);
//                 $filter->between('created_at','注册时间')->datetime();
            });
        });
    }

}
