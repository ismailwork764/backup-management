<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class AdminReportingController extends Controller
{
    public function clientsOverview()
    {
        $clients = Client::with(['agents' => function ($query) {
            $query->select('id', 'client_id', 'hostname', 'last_seen_at', 'last_backup_at');
        }])->get(['id', 'name', 'registration_key', 'storage_server_id']);

        $response = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'registration_key' => $client->registration_key,
                'storage_server' => $client->storageServer?->name ?? null,
                'agents' => $client->agents->map(function ($agent) {
                    return [
                        'id' => $agent->id,
                        'hostname' => $agent->hostname,
                        'last_seen_at' => $agent->last_seen_at,
                        'last_backup_at' => $agent->last_backup_at,
                    ];
                }),
            ];
        });

        return response()->json($response);
    }

    public function clientBackupHistory($clientId)
    {
        $client = Client::with(['backupLogs' => function ($q) {
            $q->orderByDesc('created_at')->limit(50);
        }])->findOrFail($clientId);

        $logs = $client->backupLogs->map(function ($log) {
            return [
                'agent_id' => $log->agent_id,
                'result' => $log->result,
                'message' => $log->message,
                'timestamp' => $log->created_at,
            ];
        });

        return response()->json([
            'client_id' => $client->id,
            'client_name' => $client->name,
            'backup_logs' => $logs,
        ]);
    }
}
