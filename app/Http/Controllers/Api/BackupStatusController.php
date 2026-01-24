<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use Illuminate\Http\Request;
use App\Models\Alert;

class BackupStatusController extends Controller
{
    public function store(Request $request){

        $agent = $request->attributes->get('agent');

        $request->validate([
            'status' => 'required|in:success,failed',
            'size_gb' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:2000',
        ]);

        Backup::create([
            'agent_id' => $agent->id,
            'status' => $request->status,
            'size_gb' => $request->size_gb,
            'message' => $request->message,
            'created_at' => now(),
        ]);
        
        // Update agent's last backup timestamp if successful
        if ($request->status === 'success') {
            $agent->update([
                'last_backup_at' => now(),
            ]);
        }
        if ($request->status === 'failed') {
            Alert::firstOrCreate(
                [
                    'type' => 'failed_backup',
                    'subject_type' => get_class($agent),
                    'subject_id' => $agent->id,
                ],
                [
                    'message' => "Backup failed for agent {$agent->hostname}",
                ]
            );
        }

        return response()->json([
            'message' => 'Backup recorded'
        ]);
    }
}
