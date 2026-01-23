<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'hostname' => 'required|string|max:255',
            'registration_key' => 'required|string|size:11',
        ]);

        $client = Client::where('registration_key', $request->registration_key)
            ->where('is_active', true)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Invalid registration key'
            ], 401);
        }

        $plainToken = Str::random(40);

        $agent = Agent::create([
            'client_id' => $client->id,
            'hostname' => $request->hostname,
            'api_token' => $plainToken,
            'last_seen_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'api_token' => $plainToken,
            'storage' => [
                'server' => $client->storageServer->name,
                'region' => $client->storageServer->region,
                'subaccount_id' => $client->hetzner_subaccount_id,
            ],
        ]);
    }
}
