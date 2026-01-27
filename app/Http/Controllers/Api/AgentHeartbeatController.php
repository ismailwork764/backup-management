<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentHeartbeatController extends Controller
{
    public function store(Request $request)
    {
        
        $agent = $request->attributes->get('agent');
        if (!$agent) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $agent->update([
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Heartbeat received',
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}
