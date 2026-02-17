<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Backup Summary</title>
</head>
<body>
    <p>Hello {{ $client->name }},</p>

    <p>Here is your daily backup summary with the latest three backups.</p>

    @if($backups->isEmpty())
        <p>No backups are available yet.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Agent</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                    <tr>
                        <td>{{ $backup->created_at ? $backup->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                        <td>{{ $backup->agent->hostname ?? 'Unknown' }}</td>
                        <td>{{ ucfirst($backup->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>Sent at {{ now()->format('Y-m-d H:i:s') }}</p>
</body>
</html>
