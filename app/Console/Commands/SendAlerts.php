<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use Illuminate\Support\Facades\Mail;

class SendAlerts extends Command
{
    protected $signature = 'alerts:send';
    protected $description = 'Send pending alert notifications';

    public function handle()
    {
        Alert::whereNull('sent_at')->each(function ($alert) {

            Mail::raw($alert->message, function ($mail) {
                $mail->to(config('mail.admin_address'))
                     ->subject('Backup Console Alert');
            });

            $alert->update(['sent_at' => now()]);
        });

        return Command::SUCCESS;
    }
}
