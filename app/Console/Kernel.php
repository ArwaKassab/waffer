<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(fn()=> \App\Models\DeviceToken::where('last_used_at','<',now()->subDays(90))->delete())
            ->daily();

        //  حذف إشعارات قديمة
        $schedule->call(fn()=> \App\Models\Notification::where('created_at','<',now()->subMonths(6))->delete())
            ->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
