<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageServer;
use App\Models\StorageBoxType;
use App\Models\Location;
use App\Models\Client;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class StorageServerController extends Controller
{

    public function index()
    {
        return view('admin.storage_servers');
    }

    public function create()
    {
        try {
            $storageBoxTypes = StorageBoxType::all();
            $locations = Location::all();

            return view('admin.storage_servers.create', compact('storageBoxTypes', 'locations'));
        } catch (\Exception $e) {
            return redirect()->route('admin.storage_servers.index')
                ->withErrors(['error' => 'Failed to load storage box types and locations from database: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string',
            'storage_box_type' => 'required|string',
            'password' => 'required|string|min:12',
        ]);

        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);

            $labels = [];
            if ($request->has('labels') && is_array($request->input('labels'))) {
                foreach ($request->input('labels') as $key => $value) {
                    if (!empty($key)) {
                        $labels[$key] = $value;
                    }
                }
            }

            $accessSettings = [
                'reachable_externally' => $request->has('reachable_externally'),
                'samba_enabled' => $request->has('samba_enabled'),
                'ssh_enabled' => $request->has('ssh_enabled'),
                'webdav_enabled' => $request->has('webdav_enabled'),
                'zfs_enabled' => $request->has('zfs_enabled'),
            ];

            $storageBox = $hetznerService->createStorageBox([
                'name' => $request->name,
                'location' => $request->location,
                'storage_box_type' => $request->storage_box_type,
                'password' => $request->password,
                'labels' => $labels,
                'ssh_keys' => $request->input('ssh_keys', []),
                'access_settings' => $accessSettings,
            ]);

            StorageServer::create([
                'hetzner_id' => $storageBox['id'],
                'name' => $storageBox['name'],
                'username' => $storageBox['username'] ?? '',
                'password' => $request->password,
                'server_address' => $storageBox['server'] ?? null,
                'region' => $storageBox['location']['name'] ?? $request->location,
                'api_token' => $storageBox['username'] ?? '',
                'total_capacity_gb' => round(($storageBox['storage_box_type']['size'] ?? 0) / 1024 / 1024 / 1024, 2),
                'used_capacity_gb' => 0,
                'status' => $storageBox['status'] ?? 'active',
            ]);

            return redirect()->route('admin.storage_servers.index')
                ->with('success', 'Storage box created successfully! Please save your password: ' . $request->password);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create storage box: ' . $e->getMessage()]);
        }
    }

    public function apiIndex()
    {
        $servers = StorageServer::withCount('clients');

        return DataTables::of($servers)
            ->addColumn('usage', function($server){
                $percent = $server->total_capacity_gb
                    ? round(($server->used_capacity_gb / $server->total_capacity_gb) * 100, 2)
                    : 0;
                return $server->getFormattedUsedCapacity() . ' / ' . $server->getFormattedTotalCapacity() . ' (' . $percent . '%)';
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
                $showBtn = '<a href="'.route('admin.storage_servers.show', $server->id).'" class="btn btn-sm btn-info mr-1">
                    <i class="fas fa-eye"></i> Show
                </a>';

                $deleteBtn = '<form action="'.route('admin.storage_servers.destroy', $server->id).'" method="POST" class="d-inline"
                    onsubmit="return confirm(\'Are you sure? This will permanently delete the storage box from Hetzner. This action cannot be undone.\');">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="'.csrf_token().'">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>';

                return $showBtn . $deleteBtn;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function show(StorageServer $storageServer)
    {
        $storageServer->load(['clients' => function($query) {
            $query->withCount('agents');
        }]);

        return view('admin.storage_servers.show', compact('storageServer'));
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
        if ($client->storage_server_id !== $storageServer->id) {
            return back()->withErrors(['error' => 'Client does not belong to this storage server']);
        }

        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);

            $hetznerService->deleteSubAccount(
                $storageServer->hetzner_id,
                $client->hetzner_subaccount_id
            );

            $client->delete();

            return redirect()->route('admin.storage_servers.show', $storageServer->id)
                ->with('success', 'Subaccount and client deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete subaccount: ' . $e->getMessage()]);
        }
    }

    public function destroy(StorageServer $storageServer)
    {
        try {
            if ($storageServer->clients()->count() > 0) {
                return back()->withErrors(['error' => 'Cannot delete storage server that has clients. Please delete all clients first.']);
            }

            $hetznerService = app(\App\Services\HetznerStorageService::class);

            $hetznerService->deleteStorageBox($storageServer->hetzner_id);

            $storageServer->delete();

            return redirect()->route('admin.storage_servers.index')
                ->with('success', 'Storage box deleted successfully from Hetzner and your database.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete storage box: ' . $e->getMessage()]);
        }
    }
}
