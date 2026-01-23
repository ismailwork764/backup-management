<?php
use App\Models\StorageServer;

class SyncStorageUsageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        StorageServer::where('status', 'active')->each(function ($server) {

            $usedGb = rand(10, $server->total_capacity_gb);

            $server->update([
                'used_capacity_gb' => $usedGb,
            ]);

            $utilization = round(
                ($usedGb / $server->total_capacity_gb) * 100
            );

            if ($utilization >= 80) {
                event(new StorageThresholdReached($server, $utilization));
            }
        });
    }
}
