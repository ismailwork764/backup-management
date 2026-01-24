<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageServer;
use App\Models\Client;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;

class StorageServerController extends Controller
{

    public function index()
    {
        return view('admin.storage_servers');
    }

    public function apiIndex()
    {
        $servers = StorageServer::withCount('clients');

        return DataTables::of($servers)
            ->addColumn('usage', function($server){
                $percent = $server->total_capacity_gb
                    ? round(($server->used_capacity_gb / $server->total_capacity_gb) * 100)
                    : 0;
                return $server->used_capacity_gb . ' GB / ' . $server->total_capacity_gb . ' GB (' . $percent . '%)';
            })
            ->addColumn('clients_count', function($server){
                return $server->clients_count ?? 0;
            })
            ->addColumn('status_badge', function($server){
                return $server->status === 'active'
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>';
            })
            ->addColumn('actions', function($server){
                return '<a href="'.route('admin.storage_servers.show', $server->id).'" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> Show
                </a>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function show(StorageServer $storageServer)
    {
        $storageServer->load(['clients' => function($query) {
            $query->withCount('agents');
        }]);

        // Get fresh data from Hetzner API
        $hetznerData = null;
        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);
            $hetznerData = $hetznerService->getStorageBox($storageServer->hetzner_id);
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch Hetzner data for storage server ' . $storageServer->id . ': ' . $e->getMessage());
        }

        return view('admin.storage_servers.show', compact('storageServer', 'hetznerData'));
    }

    public function sync()
    {
        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);
            $hetznerService->syncStorageBoxes();

            return redirect()->route('admin.storage_servers.index')
                ->with('success', 'Storage servers synced successfully from Hetzner!');
        } catch (\Exception $e) {
            return redirect()->route('admin.storage_servers.index')
                ->withErrors(['error' => 'Failed to sync storage servers: ' . $e->getMessage()]);
        }
    }

    public function destroySubaccount(StorageServer $storageServer, Client $client)
    {
        // Ensure client belongs to this storage server
        if ($client->storage_server_id !== $storageServer->id) {
            return back()->withErrors(['error' => 'Client does not belong to this storage server']);
        }

        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);

            // Delete subaccount from Hetzner
            $hetznerService->deleteSubAccount(
                $storageServer->hetzner_id,
                $client->hetzner_subaccount_id
            );

            // Delete client (this will cascade delete agents and backups)
            $client->delete();

            return redirect()->route('admin.storage_servers.show', $storageServer->id)
                ->with('success', 'Subaccount and client deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete subaccount: ' . $e->getMessage()]);
        }
    }
}
