<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncClientDiskUsage::class,
    ];

    protected function schedule($schedule)
    {
        // $schedule->command('clients:sync-disk-usage')->daily();
    }
}
