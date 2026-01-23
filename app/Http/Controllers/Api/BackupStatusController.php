<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\BackupLog;

class BackupStatusController extends Controller
{
    public function store(Request $request){

        $request->validate([
            'result' => 'required|in:success,failure',
            'message' => 'nullable|string',
        ]);

        $agent = $request->attributes->get('agent');

        if (!$agent) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $log = BackupLog::create([
            'agent_id' => $agent->id,
            'client_id' => $agent->client->id,
            'result' => $request->result,
            'message' => $request->message,
            'created_at' => now(),
        ]);

        $agent->last_backup_at = now();
        $agent->save();

        return response()->json([
            'message' => 'Backup status recorded'
        ]);
    }
}
