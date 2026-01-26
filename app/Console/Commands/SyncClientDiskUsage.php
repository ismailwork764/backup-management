<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\StorageServer;

use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SyncClientDiskUsage extends Command
{
    protected $signature = 'clients:sync-disk-usage';
    protected $description = 'Sync disk usage for each client by checking their home directory on storage boxes';

    public function handle()
    {
        $clients = Client::with('storageServer')->get();
        foreach ($clients as $client) {
            $server = $client->storageServer;
            if (!$server) {
                $this->warn("No storage server for client {$client->name}");
                continue;
            }
            $host = $server->server_address;
            $username = $client->hetzner_username;
            $homeDir = $client->hetzner_subaccount_home ?? "/home/{$username}";

            // Path to private key (update this path as needed)
            $privateKeyPath = storage_path('app/ssh/id_rsa');
            if (!file_exists($privateKeyPath)) {
                $this->error("Private key not found at $privateKeyPath");
                return 1;
            }
            $key = PublicKeyLoader::loadPrivateKey(file_get_contents($privateKeyPath));

            $ssh = new SSH2($host);
            if (!$ssh->login($username, $key)) {
                Log::warning("SSH login failed for client {$client->name}");
                $this->warn("SSH login failed for {$client->name}");
                continue;
            }
            $result = $ssh->exec("du -sb $homeDir | cut -f1");
            if ($result !== false && is_numeric(trim($result))) {
                $bytes = (int) trim($result);
                $client->disk_usage_bytes = $bytes;
                $client->save();
                $this->info("{$client->name}: $bytes bytes");
            } else {
                Log::warning("Failed to get disk usage for client {$client->name}");
                $this->warn("Failed for {$client->name}");
            }
        }
    }
}
