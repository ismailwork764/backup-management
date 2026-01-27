@extends('adminlte::page')

@section('title', 'Client Details')

@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>Client Details: {{ $client->name }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h5>Client Info</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $client->name }}</td>
                </tr>
                <tr>
                    <th>Storage Server</th>
                    <td>{{ $client->storageServer->name }} ({{ $client->storageServer->region }})</td>
                </tr>
                <tr>
                    <th>Server Address</th>
                    <td>{{ $client->storageServer->server_address }}</td>
                </tr>
                <tr>
                    <th>Quota (GB)</th>
                    <td>{{ $client->quota_gb }}</td>
                </tr>
                <tr>
                    <th>Access Protocols</th>
                    <td>
                        @php
                            $protocols = [
                                'Reachable Externally' => $client->reachable_externally,
                                'SSH' => $client->ssh_enabled,
                                'Samba/SMB' => $client->samba_enabled,
                                'WebDAV' => $client->webdav_enabled,
                                'Read-Only' => $client->readonly,
                            ];
                        @endphp
                        @foreach($protocols as $name => $enabled)
                            @if($enabled)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> {{ $name }}
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-times"></i> {{ $name }}
                                </span>
                            @endif
                        @endforeach
                    </td>
                </tr>
                @if($diskUtilization)
                <tr>
                    <th>Disk Utilization</th>
                    <td>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $diskUtilization['percentage'] > 80 ? 'bg-danger' : ($diskUtilization['percentage'] > 60 ? 'bg-warning' : 'bg-success') }}"
                                 role="progressbar"
                                 style="width: {{ $diskUtilization['percentage'] }}%"
                                 aria-valuenow="{{ $diskUtilization['percentage'] }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ $diskUtilization['used_gb'] }} GB / {{ $diskUtilization['quota_gb'] }} GB ({{ $diskUtilization['percentage'] }}%)
                            </div>
                        </div>
                    </td>
                </tr>
                @else
                <tr>
                    <th>Disk Utilization</th>
                    <td><span class="text-muted">Not available</span></td>
                </tr>
                @endif
                <tr>
                    <th>Registration Key</th>
                    <td>
                        <input type="text" value="{{ $client->registration_key }}" readonly class="form-control d-inline w-auto">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('{{ $client->registration_key }}')">Copy</button>
                    </td>
                </tr>
                <tr>
                    <th>Hetzner Username</th>
                    <td>
                        <input type="text" value="{{ $client->hetzner_username }}" readonly class="form-control d-inline w-auto">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('{{ $client->hetzner_username }}')">Copy</button>
                    </td>
                </tr>
                <tr>
                    <th>Hetzner Password</th>
                    <td>
                        <input type="text" value="{{ $client->hetzner_password }}" readonly class="form-control d-inline w-auto">
                        <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('{{ $client->hetzner_password }}')">Copy</button>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Registered Backup Agents</h5>
                <a href="{{ route('admin.clients.agents.index', $client->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-list"></i> View All Agents
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($client->agents->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Hostname</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Last Backup</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->agents as $agent)
                            <tr>
                                <td>{{ $agent->hostname }}</td>
                                <td>
                                    @if($agent->last_seen_at && $agent->last_seen_at->isAfter(now()->subMinutes(5)))
                                        <span class="badge badge-success">Online</span>
                                    @else
                                        <span class="badge badge-danger">Offline</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $agent->last_seen_at ? $agent->last_seen_at->format('Y-m-d H:i:s') : 'Never' }}
                                </td>
                                <td>
                                    @if($agent->last_backup_at)
                                        {{ $agent->last_backup_at->format('Y-m-d H:i:s') }}
                                    @else
                                        <span class="text-muted">No backups yet</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.clients.agents.destroy', [$client->id, $agent->id]) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this agent? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No agents registered yet.</p>
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Recent Backup History</h5>
        </div>
        <div class="card-body">
            @if($recentBackups->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Size (GB)</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBackups as $backup)
                            <tr>
                                <td>{{ $backup->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $backup->agent->hostname ?? 'Unknown' }}</td>
                                <td>
                                    @if($backup->status === 'success')
                                        <span class="badge badge-success">Success</span>
                                    @else
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $backup->size_gb ?? '-' }}</td>
                                <td>
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($backup->message ?? '-', 50) }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No backup history available.</p>
            @endif
        </div>
    </div>

    <div class="card-footer mt-3">
        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Clients
        </a>
    </div>

@stop

@section('js')
    <script>
        function copyToClipboard(text) {
            if (!navigator.clipboard) {
                var textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Content copied to clipboard',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                }
                document.body.removeChild(textArea);
                return;
            }
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Content copied to clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            }, function(err) {
                console.error('Async: Could not copy text: ', err);
            });
        }
    </script>
@stop
