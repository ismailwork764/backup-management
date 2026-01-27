<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StorageServer;
use App\Models\Alert;

class CheckStorageUsage extends Command
{
    protected $signature = 'alerts:storage-usage';
    protected $description = 'Detect storage servers above 80% usage';

    public function handle()
    {
        StorageServer::all()->each(function ($server) {

            if (!$server->total_capacity_gb) {
                return;
            }

            $usage = ($server->used_capacity_gb / $server->total_capacity_gb) * 100;

            if ($usage >= 80) {
                Alert::firstOrCreate(
                    [
                        'type' => 'storage_threshold',
                        'subject_type' => get_class($server),
                        'subject_id' => $server->id,
                        'sent_at' => null,
                    ],
                    [
                        'message' => "Storage server {$server->name} is at {$usage}% usage",
                    ]
                );
            }
        });

        return Command::SUCCESS;
    }
}
