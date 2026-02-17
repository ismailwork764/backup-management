<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncClientDiskUsage::class,
        \App\Console\Commands\SyncStorageBoxes::class,
        \App\Console\Commands\CheckMissingBackups::class,
        \App\Console\Commands\CheckStorageUsage::class,
        \App\Console\Commands\SendAlerts::class,
        \App\Console\Commands\SendDailyClientBackupSummary::class,
    ];

    protected function schedule($schedule)
    {
        $schedule->command('storage-boxes:sync')->everyMinute();
        $schedule->command('clients:sync-disk-usage')->everyMinute();

        $schedule->command('alerts:no-backup')->daily();
        $schedule->command('alerts:storage-usage')->hourly();
        $schedule->command('alerts:send')->everyMinute();
        $schedule->command('clients:send-daily-backup-summary')->dailyAt('04:00');
    }
}
