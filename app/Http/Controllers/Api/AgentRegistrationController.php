<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AgentRegistrationController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'hostname' => 'required|string|max:255',
            'registration_key' => 'required|string|size:11',
        ]);

        $client = Client::with('storageServer')
            ->where('registration_key', $request->registration_key)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Invalid registration key'
            ], 401);
        }

        // Validate client has required storage information
        if (!$client->storageServer) {
            return response()->json([
                'message' => 'Client storage server not configured'
            ], 500);
        }

        if (!$client->storageServer->hetzner_id) {
            return response()->json([
                'message' => 'Storage server Hetzner ID not configured'
            ], 500);
        }

        if (!$client->hetzner_username || !$client->hetzner_password) {
            return response()->json([
                'message' => 'Client storage credentials not configured'
            ], 500);
        }

        $plainToken = Str::random(40);
        $agent = Agent::create([
            'client_id' => $client->id,
            'hostname' => $request->hostname,
            'api_token' => $plainToken,
            'last_seen_at' => now(),
            'is_active' => true,
        ]);


        // Get storage box details for connection info
        $storageServer = $client->storageServer;
        $hetznerService = app(\App\Services\HetznerStorageService::class);

        // Fetch storage box details to get hostname (with error handling)
        $storageBoxDetails = null;
        $storageHostname = null;
        try {
            $storageBoxDetails = $hetznerService->getStorageBox($storageServer->hetzner_id);
            $storageHostname = $storageBoxDetails['server'] ?? null;
        } catch (\Exception $e) {
            // Log error but continue - we can still provide credentials
            Log::warning('Failed to fetch storage box details during agent registration: ' . $e->getMessage());
        }

        return response()->json([
            'api_token' => $plainToken,
            'storage' => [
                'hostname' => $storageHostname,
                'username' => $client->hetzner_username,
                'password' => $client->hetzner_password,
                'subaccount_id' => $client->hetzner_subaccount_id,
                'server_name' => $storageServer->name,
                'region' => $storageServer->region,
                'webdav_url' => $storageHostname ? 'https://' . $storageHostname . '/dav/' : null,
                'sftp_host' => $storageHostname,
                'sftp_port' => 23,
            ],
        ]);
    }
}
