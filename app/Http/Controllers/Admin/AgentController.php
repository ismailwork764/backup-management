<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agent;

class AgentController extends Controller
{
    public function index(){

        $agents = Agent::with('client:id,name')
            ->orderBy('last_seen_at', 'desc')
            ->get()
            ->map(function ($agent) {

                return [
                    'id' => $agent->id,
                    'hostname' => $agent->hostname,
                    'client' => $agent->client->name,
                    'last_seen_at' => $agent->last_seen_at,
                    'status' => $agent->last_seen_at >= now()->subMinutes(5)
                        ? 'online'
                        : 'offline',
                ];
            });

        return response()->json($agents);
    }

}
