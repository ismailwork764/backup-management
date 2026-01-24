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
                $agentsBtn = '<a href="/admin/clients/'.$client->id.'/agents" class="btn btn-sm btn-primary">
                    <i class="fas fa-server"></i> View Agents
                </a>';
                return $showBtn . $agentsBtn;
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
        ]);

        $storageServer = StorageServer::findOrFail($request->storage_server_id);

        DB::beginTransaction();

        try {
            // 1️⃣ Generate unique registration key
            do {
                $registrationKey = strtoupper(Str::random(5) . '-' . Str::random(5));
            } while (Client::where('registration_key', $registrationKey)->exists());

            // 2️⃣ Create Hetzner sub-account via service
            $hetznerService = app(\App\Services\HetznerStorageService::class);
            $password = $request->password ?? $this->generateStrongPassword(16);

            // Minimum resources: smallest quota and default home folder
            $subAccount = $hetznerService->createSubAccount([
                'storage_box_id' => $storageServer->hetzner_id, // Hetzner ID
                'name' => $request->name,                       // optional subaccount name
                'password' => $password,
                'home_directory' => '/',                        // default home dir
            ]);

            // Validate response structure
            if (!isset($subAccount['username']) || !isset($subAccount['id'])) {
                throw new \Exception('Invalid Hetzner API response: missing required fields. Response: ' . json_encode($subAccount));
            }

            $hetznerUsername = $subAccount['username'];
            $hetznerPassword = $subAccount['password'] ?? $password; // Use provided password if API doesn't return it
            $hetznerSubaccountId = $subAccount['id'];

            // 3️⃣ Store client in DB
            $client = Client::create([
                'name' => $request->name,
                'storage_server_id' => $storageServer->id,
                'hetzner_subaccount_id' => $hetznerSubaccountId,
                'registration_key' => $registrationKey,
                'is_active' => true,
                'quota_gb' => $request->quota_gb,
                'hetzner_username' => $hetznerUsername,
                'hetzner_password' => $hetznerPassword,
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

        // Get disk utilization from Hetzner
        $diskUtilization = null;
        try {
            $hetznerService = app(\App\Services\HetznerStorageService::class);
            $subAccount = $hetznerService->getSubAccount(
                $client->storageServer->hetzner_id,
                $client->hetzner_subaccount_id
            );

            // Calculate utilization from subaccount data
            if (isset($subAccount['disk_quota']) && isset($subAccount['disk_usage'])) {
                $diskUtilization = [
                    'used_gb' => round($subAccount['disk_usage'] / 1024 / 1024 / 1024, 2),
                    'quota_gb' => round($subAccount['disk_quota'] / 1024 / 1024 / 1024, 2),
                    'percentage' => $subAccount['disk_quota'] > 0
                        ? round(($subAccount['disk_usage'] / $subAccount['disk_quota']) * 100, 2)
                        : 0,
                ];
            }
        } catch (\Exception $e) {
            // If we can't fetch utilization, continue without it
            Log::warning('Failed to fetch disk utilization for client ' . $client->id . ': ' . $e->getMessage());
        }

        // Get recent backups
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

    public function destroyAgent(Client $client, Agent $agent)
    {
        // Ensure agent belongs to client
        if ($agent->client_id !== $client->id) {
            return back()->withErrors(['error' => 'Agent does not belong to this client']);
        }

        $agent->delete();

        return back()->with('success', 'Agent deleted successfully');
    }
    public function generateStrongPassword(int $length = 16): string
    {
        // Hetzner allowed characters: a-z A-Z Ä Ö Ü ä ö ü ß 0-9 ^ ° ! § $ % / ( ) = ? + # - . , ; : ~ * @ { } _ &
        // Define characters explicitly to avoid encoding issues
        $upper = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $lower = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
        $numbers = ['0','1','2','3','4','5','6','7','8','9'];
        // Special characters: ^ ° ! § $ % / ( ) = ? + # - . , ; : ~ * @ { } _ &
        $special = ['^', '°', '!', '§', '$', '%', '/', '(', ')', '=', '?', '+', '#', '-', '.', ',', ';', ':', '~', '*', '@', '{', '}', '_', '&'];

        // Ensure at least one of each type
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
