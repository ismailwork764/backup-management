<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Agent;
use Yajra\DataTables\DataTables;
use App\Models\StorageServer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ClientController extends Controller
{
    public function index()
    {
        return view('admin.clients');
    }

    public function apiIndex()
    {
        $clients = Client::withCount('agents')->with('storageServer');

        return DataTables::of($clients)
            ->addColumn('storage_server', function($client){
                return $client->storageServer->name ?? '-';
            })
            ->addColumn('agents_count', function($client){
                return $client->agents_count;
            })
            ->addColumn('last_backup', function($client){
                $backup = $client->agents()
                    ->with('backups')
                    ->get()
                    ->pluck('backups')
                    ->flatten()
                    ->sortByDesc('created_at')
                    ->first();

                return $backup ? $backup->created_at->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function($client){
                $showBtn = '<a href="'.route('admin.clients.show', $client->id).'" class="btn btn-sm btn-info mr-1">
                    <i class="fas fa-eye"></i> Show
                </a>';
                $agentsBtn = '<a href="'.route('admin.clients.agents.index', $client->id).'" class="btn btn-sm btn-primary">
                    <i class="fas fa-server"></i> Agents
                </a>';

                $deleteBtn = '<form action="'.route('admin.clients.destroy', $client->id).'" method="POST" class="d-inline"
                    onsubmit="return confirm(\'Are you sure you want to delete this client? This will also delete all associated agents and backups. This action cannot be undone.\');">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="'.csrf_token().'">
                    <button type="submit" class="btn btn-sm btn-danger ml-1">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>';

                return $showBtn . $agentsBtn . $deleteBtn;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.clients.create', [
            'storageServers' => StorageServer::where('status', 'active')->get(),

        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'storage_server_id' => 'required|exists:storage_servers,id',
            'quota_gb' => 'required|integer|min:10',
            'notification_email' => 'nullable|email|max:255|required_if:daily_backup_notifications_enabled,1',
            'daily_backup_notifications_enabled' => 'nullable|boolean',
        ]);

        $storageServer = StorageServer::findOrFail($request->storage_server_id);

        DB::beginTransaction();

        try {
            do {
                $registrationKey = strtoupper(Str::random(5) . '-' . Str::random(5));
            } while (Client::where('registration_key', $registrationKey)->exists());

            $hetznerService = app(\App\Services\HetznerStorageService::class);
            $password = $request->password ?? $this->generateStrongPassword(16);

            $home_directory = 'client-' . preg_replace('/\s+/', '_', $request->name);
            $subAccount = $hetznerService->createSubAccount([
                'storage_box_id' => $storageServer->hetzner_id, 
                'name' => $request->name,                       
                'password' => $password,
                'home_directory' => $home_directory,                        
                'reachable_externally' => $request->has('reachable_externally') ? true : false,
                'samba_enabled' => $request->has('samba_enabled') ? true : false,
                'ssh_enabled' => $request->has('ssh_enabled') ? true : false,
                'webdav_enabled' => $request->has('webdav_enabled') ? true : false,
                'readonly' => $request->has('readonly') ? true : false,
            ]);

            if (!isset($subAccount['username']) || !isset($subAccount['id'])) {
                throw new \Exception('Invalid Hetzner API response: missing required fields. Response: ' . json_encode($subAccount));
            }

            $hetznerUsername = $subAccount['username'];
            $hetznerPassword = $subAccount['password'] ?? $password; 
            $hetznerSubaccountId = $subAccount['id'];

            $client = Client::create([
                'name' => $request->name,
                'notification_email' => $request->input('notification_email'),
                'daily_backup_notifications_enabled' => $request->boolean('daily_backup_notifications_enabled'),
                'storage_server_id' => $storageServer->id,
                'hetzner_subaccount_id' => $hetznerSubaccountId,
                'registration_key' => $registrationKey,
                'is_active' => true,
                'quota_gb' => $request->quota_gb,
                'hetzner_username' => $hetznerUsername,
                'hetzner_password' => $hetznerPassword,
                'home_directory' => $home_directory ?? null,
                'ftp_enabled' => $request->has('ftp_enabled'),
                'sftp_enabled' => $request->has('sftp_enabled'),
                'scp_enabled' => $request->has('scp_enabled'),
                'webdav_enabled' => $request->has('webdav_enabled'),
                'samba_enabled' => $request->has('samba_enabled'),
            ]);

            DB::commit();

            return redirect()->route('admin.clients.show', $client->id)
                ->with('success', 'Client created successfully!');


        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }


    }
    public function show(Client $client)
    {
        $client->load(['storageServer', 'agents' => function($query) {
            $query->orderBy('last_seen_at', 'desc');
        }]);


        $diskUtilization = null;
        if ($client->quota_gb > 0) {
            $used_gb = round($client->disk_usage_bytes / 1024 / 1024 / 1024, 2);
            $quota_gb = $client->quota_gb;
            $percentage = $quota_gb > 0 ? round(($used_gb / $quota_gb) * 100, 2) : 0;
            $diskUtilization = [
                'used_gb' => $used_gb,
                'quota_gb' => $quota_gb,
                'percentage' => $percentage,
            ];
        }

        $recentBackups = $client->agents()
            ->with(['backups' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])
            ->get()
            ->pluck('backups')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(20);

        return view('admin.clients.show', compact('client', 'diskUtilization', 'recentBackups'));
    }

    public function destroy(Client $client)
    {
        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);

            if ($client->storageServer && $client->hetzner_subaccount_id) {
                $hetznerService->deleteSubAccount(
                    $client->storageServer->hetzner_id,
                    $client->hetzner_subaccount_id
                );
            }

            $client->delete();

            return redirect()->route('admin.clients.index')
                ->with('success', 'Client and associated subaccount deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete client: ' . $e->getMessage()]);
        }
    }

    public function destroyAgent(Client $client, Agent $agent)
    {
        if ($agent->client_id !== $client->id) {
            return back()->withErrors(['error' => 'Agent does not belong to this client']);
        }

        $agent->delete();

        return back()->with('success', 'Agent deleted successfully');
    }

    public function agentsIndex(Client $client)
    {
        $agents = $client->agents()->orderBy('last_seen_at', 'desc')->get();

        return view('admin.clients.agents', compact('client', 'agents'));
    }

    public function generateStrongPassword(int $length = 16): string
    {
        $upper = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $lower = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
        $numbers = ['0','1','2','3','4','5','6','7','8','9'];
        $special = ['^', '°', '!', '§', '$', '%', '/', '(', ')', '=', '?', '+', '#', '-', '.', ',', ';', ':', '~', '*', '@', '{', '}', '_', '&'];

        $password = [
            $upper[random_int(0, count($upper)-1)],
            $lower[random_int(0, count($lower)-1)],
            $numbers[random_int(0, count($numbers)-1)],
            $special[random_int(0, count($special)-1)],
        ];

        $all = array_merge($upper, $lower, $numbers, $special);

        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[random_int(0, count($all)-1)];
        }

        shuffle($password);

        return implode('', $password);
    }



}
