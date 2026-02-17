<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyClientBackupSummary extends Command
{
    protected $signature = 'clients:send-daily-backup-summary';
    protected $description = 'Send daily backup summary emails to clients who opted in';

    public function handle(): int
    {
        $clients = Client::query()
            ->where('is_active', true)
            ->where('daily_backup_notifications_enabled', true)
            ->whereNotNull('notification_email')
            ->get();

        foreach ($clients as $client) {
            $backups = Backup::query()
                ->with('agent')
                ->select('backups.*')
                ->join('agents', 'agents.id', '=', 'backups.agent_id')
                ->where('agents.client_id', $client->id)
                ->orderByDesc('backups.created_at')
                ->limit(3)
                ->get();

            Mail::send('emails.client_backup_summary', [
                'client' => $client,
                'backups' => $backups,
            ], function ($mail) use ($client) {
                $mail->to($client->notification_email)
                    ->subject('Daily Backup Summary');
            });
        }

        return Command::SUCCESS;
    }
}
