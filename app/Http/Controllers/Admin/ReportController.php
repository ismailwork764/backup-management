<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Backup;
use App\Models\MonthlyUsage;
use App\Services\HetznerStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{

    public function index()
    {
        return view('admin.reports');
    }

    public function apiIndex()
    {
        $backups = Backup::select(
                'clients.id as client_id',
                'clients.name as client_name',
                DB::raw('DATE_FORMAT(backups.created_at, "%Y-%m") as month'),
                DB::raw('SUM(backups.size_gb) as total_gb'),
                DB::raw('SUM(CASE WHEN backups.status = "failed" THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('COUNT(*) as total_backups')
            )
            ->join('agents', 'backups.agent_id', '=', 'agents.id')
            ->join('clients', 'agents.client_id', '=', 'clients.id')
            ->groupBy(
                'clients.id',
                'clients.name',
                DB::raw('DATE_FORMAT(backups.created_at, "%Y-%m")')
            );

        return DataTables::of($backups)
            ->editColumn('total_gb', function($row){
                return number_format($row->total_gb, 2) . ' GB';
            })
            ->make(true);
    }
    
    public function storageUtilization()
    {
        $clients = Client::with('storageServer')->where('is_active', true)->get();
        $utilization = [];
        foreach ($clients as $client) {
            $usedGb = 0;
            $quotaGb = (float) $client->quota_gb;
            $percentage = 0;

            if ($quotaGb > 0) {
                $usedGb = round(($client->disk_usage_bytes ?? 0) / 1024 / 1024 / 1024, 2);
                $percentage = round(($usedGb / $quotaGb) * 100);
            }
            
            $utilization[] = [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'storage_server' => $client->storageServer->name ?? '-',
                'used_gb' => $usedGb,
                'quota_gb' => $quotaGb,
                'percentage' => $percentage,
                'available_gb' => max(0, $quotaGb - $usedGb),
            ];
        }
        
        return DataTables::of(collect($utilization))
            ->addColumn('usage_bar', function($row) {
                $colorClass = $row['percentage'] > 80 ? 'bg-danger' : ($row['percentage'] > 60 ? 'bg-warning' : 'bg-success');
                return '<div class="progress" style="height: 20px;">
                    <div class="progress-bar ' . $colorClass . '"
                        style="width:' . $row['percentage'] . '%"
                        aria-valuenow="' . $row['percentage'] . '"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        title="' . $row['percentage'] . '%">
                    </div>
                </div>';
            })
            ->editColumn('used_gb', function($row) {
                return number_format($row['used_gb'], 2) . ' GB';
            })
            ->editColumn('quota_gb', function($row) {
                return number_format($row['quota_gb'], 2) . ' GB';
            })
            ->editColumn('available_gb', function($row) {
                return number_format($row['available_gb'], 2) . ' GB';
            })
            ->addColumn('actions', function($row) {
                return '<a href="' . route('admin.clients.show', $row['client_id']) . '" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> View
                </a>';
            })
            ->rawColumns(['usage_bar', 'actions'])
            ->make(true);
    }
    
    public function monthlyUsage()
    {
        $monthlyUsage = MonthlyUsage::with('client')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('max_used_gb', 'desc');
        
        return DataTables::of($monthlyUsage)
            ->addColumn('client_name', function($usage) {
                return $usage->client->name ?? '-';
            })
            ->addColumn('month_name', function($usage) {
                return Carbon::create($usage->year, $usage->month, 1)->format('F Y');
            })
            ->editColumn('max_used_gb', function($usage) {
                return number_format($usage->max_used_gb, 2) . ' GB';
            })
            ->addColumn('actions', function($usage) {
                return '<a href="' . route('admin.clients.show', $usage->client_id) . '" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> View Client
                </a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
