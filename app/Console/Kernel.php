<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\BranchFeaturedExpire::class,
        \App\Console\Commands\ConsultingReservationExpire::class,
        \App\Console\Commands\SendDoctorSMS::class,
    ];


    protected function scheduleTimezone()
    {
        return 'Asia/Riyadh';
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('subscription:expire')->daily();
        //$schedule->command('counter:expire')->everyMinute();
        $schedule->command('doctor:sms')->everyMinute() ->runInBackground();
        $schedule->command('consulting:expire')->everyMinute() ->runInBackground();
        $schedule->command('queue:restart')->everyFiveMinutes();
        $schedule->command('queue:work')->everyMinute() ;
        $schedule->command('queue:retry all')->everyTenMinutes();
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
