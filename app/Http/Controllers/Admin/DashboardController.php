<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\StorageServer;
use App\Models\Backup;
use App\Models\Alert;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(){
        return view('admin.dashboard');
    }
    public function summary(){

        $totalClients = Client::count();

        $activeAgents = Agent::where('is_active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->count();

        $offlineAgents = Agent::where('is_active', true)
            ->where('last_seen_at', '<', now()->subMinutes(5))
            ->count();

        $storage = StorageServer::selectRaw('
            SUM(total_capacity_gb) as total,
            SUM(used_capacity_gb) as used
        ')->first();

        $failedBackups = Backup::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $recentAlerts = Alert::where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->get(['id', 'message', 'type', 'created_at']);

        return response()->json([
            'clients' => $totalClients,
            'agents' => [
                'active' => $activeAgents,
                'offline' => $offlineAgents,
            ],
            'storage' => [
                'total_gb' => (int) $storage->total,
                'used_gb' => (int) $storage->used,
                'usage_percent' => $storage->total
                    ? round(($storage->used / $storage->total) * 100)
                    : 0,
            ],
            'failed_backups_24h' => $failedBackups,
            'recent_alerts' => $recentAlerts
        ]);
    }
}
