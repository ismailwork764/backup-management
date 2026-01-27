<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HetznerStorageService;
use Illuminate\Support\Facades\Log;

class SyncStorageBoxes extends Command
{
    protected $signature = 'storage-boxes:sync';

    protected $description = 'Sync storage boxes from Hetzner API';

    public function handle()
    {
        $this->info('Starting sync of storage boxes...');
        
        try {
            $hetznerService = app(HetznerStorageService::class);
            $hetznerService->syncStorageBoxes();
            $hetznerService->syncMetadata();
            $this->info('Successfully synced storage boxes and metadata.');
        } catch (\Exception $e) {
            Log::error('Failed to sync storage boxes: ' . $e->getMessage());
            $this->error('Failed to sync storage boxes: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
