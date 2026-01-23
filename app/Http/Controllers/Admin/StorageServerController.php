<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StorageServer;

class StorageServerController extends Controller
{
    public function index(){

        return StorageServer::all()->map(function ($server) {

            $usage = $server->total_capacity_gb
                ? round(($server->used_capacity_gb / $server->total_capacity_gb) * 100)
                : 0;

            return [
                'id' => $server->id,
                'name' => $server->name,
                'region' => $server->region,
                'total_gb' => $server->total_capacity_gb,
                'used_gb' => $server->used_capacity_gb,
                'usage_percent' => $usage,
                'status' => $usage >= 85 ? 'critical' : 'healthy',
            ];
        });
    }

}
