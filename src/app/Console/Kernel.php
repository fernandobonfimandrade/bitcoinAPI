<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Models\BitcoinSelling;
use App\Logger\BaseLogger;

class Kernel extends ConsoleKernel
{
    /**
     * @var BaseLogger
     */
    private $baseLogger;
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
        $schedule->call(function () {

            $this->baseLogger = new BaseLogger;
            $this->baseLogger->init('logs/schedules_logs/schedules_' . date('Y-m-d') . '.log');

            $transactions = BitcoinSelling::where('processed', false)->get();

            $this->baseLogger->info('LOG FROM SCHEDULE BITCOIN TRANSACTIONS: '.json_encode($transactions));

        })->everyMinute();
    }
}
