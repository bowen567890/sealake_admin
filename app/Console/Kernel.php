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
//         $schedule->command('sync:tokenprice')->cron('*/2 * * * *');
        //保证金订单释放
        $schedule->command('FeeInsuranceOrder')->cron('* * * * *');
        
        //池子奖励 每日0点10分分
        $schedule->command('SyncPoolReward')->cron('10 0 * * *');
        
        //入场券日志 不在这里执行 脚本每10秒执行
        //         $schedule->command('command:SyncTicketEvent')->cron('* * * * *');
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
