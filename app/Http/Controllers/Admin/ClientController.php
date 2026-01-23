<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(){

        $clients = Client::with([
            'agents:id,client_id,last_seen_at,is_active',
            'storageServer:id,name,region'
        ])->get()->map(function ($client) {

            $lastSeen = $client->agents->max('last_seen_at');

            return [
                'id' => $client->id,
                'name' => $client->name,
                'agents' => $client->agents->count(),
                'last_agent_seen' => $lastSeen,
                'storage_server' => $client->storageServer?->name,
                'status' => $lastSeen && $lastSeen >= now()->subMinutes(5)
                    ? 'online'
                    : 'attention',
            ];
        });

        return response()->json($clients);
    }

}
