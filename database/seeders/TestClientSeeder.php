<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\StorageServer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestClientSeeder extends Seeder
{
    public function run(): void
    {
        $storage = StorageServer::first() ?? StorageServer::create([
            'name' => 'Hetzner Test Storage',
            'region' => 'fsn1',
            'api_token' => 'dummy-token',
            'total_capacity_gb' => 1000,
            'used_capacity_gb' => 0,
            'status' => 'active',
        ]);

        $client = Client::create([
            'name' => 'Test Client',
            'storage_server_id' => $storage->id,
            'hetzner_subaccount_id' => 'test-subaccount-001',
            'registration_key' => strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(5)),
            'is_active' => true,
        ]);

        $this->command->info('Test client created');
        $this->command->info('Registration Key: ' . $client->registration_key);
    }
}
