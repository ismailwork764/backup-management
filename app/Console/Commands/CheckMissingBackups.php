<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;
use App\Models\Alert;

class CheckMissingBackups extends Command
{
    protected $signature = 'alerts:no-backup';
    protected $description = 'Detect agents without backups in 3 days';

    public function handle()
    {
        $threshold = now()->subDays(3);

        Agent::whereDoesntHave('backups', function ($q) use ($threshold) {
            $q->where('created_at', '>=', $threshold);
        })->where('is_active', true)
          ->each(function ($agent) {

            Alert::firstOrCreate(
                [
                    'type' => 'no_backup',
                    'subject_type' => get_class($agent),
                    'subject_id' => $agent->id,
                    'sent_at' => null,
                ],
                [
                    'message' => "No backup in 3 days for agent {$agent->hostname}",
                ]
            );
        });

        return Command::SUCCESS;
    }
}
