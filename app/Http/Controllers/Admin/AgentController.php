<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class AgentController extends Controller
{

    public function index()
    {
        return view('admin.agents');
    }

    public function apiIndex()
    {
        $agents = Agent::with('client');

        return DataTables::of($agents)
            ->addColumn('client_name', function($agent){
                return $agent->client->name ?? '-';
            })
            ->addColumn('status', function($agent){
                return $agent->last_seen_at >= now()->subMinutes(5)
                    ? '<span class="badge badge-success">Online</span>'
                    : '<span class="badge badge-danger">Offline</span>';
            })
            ->addColumn('last_seen', function($agent){
                return $agent->last_seen_at ? $agent->last_seen_at->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($agent) {
                $url = route('admin.agents.backups', ['agent' => $agent->id]);

                return '<a href="'.$url.'" class="btn btn-sm btn-primary">View Backups</a>';
            })

            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function backups(Agent $agent)
    {
        return view('admin.agent_backups', compact('agent'));
    }

    public function apiBackups(Agent $agent)
    {
        $backups = $agent->backups()->orderByDesc('created_at');

        return DataTables::of($backups)
            ->addColumn('status', function($backup){
                return $backup->status === 'success'
                    ? '<span class="badge badge-success">Success</span>'
                    : '<span class="badge badge-danger">Failed</span>';
            })
            ->editColumn('created_at', function($backup){
                return $backup->created_at->format('Y-m-d H:i');
            })
            ->rawColumns(['status'])
            ->make(true);
    }

}
