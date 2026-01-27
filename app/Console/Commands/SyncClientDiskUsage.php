<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Helpers\HetznerHelper;
use Illuminate\Support\Facades\Log;
use App\Models\MonthlyUsage;

class SyncClientDiskUsage extends Command
{
    protected $signature = 'clients:sync-disk-usage';

    protected $description = 'Sync disk usage for each client by checking their home directory on storage boxes';

    public function handle()
    {
        $this->info('Starting sync of client disk usage...');
        
        $clients = Client::with('storageServer')->where('is_active', true)->get();
        
        foreach ($clients as $client) {
            $server = $client->storageServer;
            
            if (!$server) {
                $this->warn("No storage server for client: {$client->name}");
                continue;
            }

            $subAccountDir = $client->home_directory;
            
            if (!$subAccountDir) {
                $this->warn("No home directory defined for client: {$client->name}");
                continue;
            }

            $this->info("Checking usage for {$client->name} in directory: {$subAccountDir}...");

            try {
                $bytes = HetznerHelper::getSubAccountUsage($server, $subAccountDir);
                
                if ($bytes !== null) {
                    $client->disk_usage_bytes = $bytes;
                    $client->save();

                    $usedGb = round($bytes / 1024 / 1024 / 1024, 2);
                    $now = now();
                    
                    $monthlyUsage = MonthlyUsage::firstOrCreate([
                        'client_id' => $client->id,
                        'year' => $now->year,
                        'month' => $now->month,
                    ], [
                        'max_used_gb' => 0
                    ]);

                    if ($usedGb > $monthlyUsage->max_used_gb) {
                        $monthlyUsage->update(['max_used_gb' => $usedGb]);
                    }

                    $this->info("Successfully updated usage for {$client->name}: " . $usedGb . " GB");
                } else {
                    $this->error("Failed to get usage for {$client->name}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync disk usage for client {$client->id}: " . $e->getMessage());
                $this->error("Error for {$client->name}: " . $e->getMessage());
            }
        }

        $this->info('Finished sync of client disk usage.');
        return 0;
    }
}
