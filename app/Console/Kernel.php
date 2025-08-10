<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ShortSchedule\ShortSchedule;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        //查询代币价格
        $schedule->command('sync:tokenprice')->cron('*/2 * * * *');
        //每小时0分普通节点池分红
        $schedule->command('command:SyncNodePool')->cron('0 * * * *');
        //每天早上8:00分配一次超级节点池分红
        $schedule->command('command:SyncNodePoolSuper')->cron('0 8 * * *');
        //每小时0分查询USD|CNY价格
        $schedule->command('sync:UsdCnyPrice')->cron('0 * * * *');
        //算力事件 不在这里执行 脚本每10秒执行
        //         $schedule->command('command:SyncPowerEvent')->cron('* * * * *');
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
