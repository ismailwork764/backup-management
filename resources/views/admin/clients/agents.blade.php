@extends('adminlte::page')

@section('title', 'Client Agents - ' . $client->name)

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Agents for {{ $client->name }}</h1>
        <a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Client
        </a>
    </div>
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
        <div class="card-header">
            <h5 class="card-title mb-0">Registered Backup Agents</h5>
        </div>
        <div class="card-body">
            @if($agents->count() > 0)
                <table id="client-agents-table" class="table table-bordered table-striped" style="width: 100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hostname</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Last Backup</th>
                            <th>Backups</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agents as $agent)
                            <tr>
                                <td>{{ $agent->id }}</td>
                                <td>{{ $agent->hostname }}</td>
                                <td>
                                    @if($agent->last_seen_at && $agent->last_seen_at->isAfter(now()->subMinutes(5)))
                                        <span class="badge badge-success">Online</span>
                                    @else
                                        <span class="badge badge-danger">Offline</span>
                                    @endif
                                </td>
                                <td>
                                    @if($agent->last_seen_at)
                                        <small>{{ $agent->last_seen_at->format('Y-m-d H:i:s') }}</small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    @if($agent->last_backup_at)
                                        <small>{{ $agent->last_backup_at->format('Y-m-d H:i:s') }}</small>
                                    @else
                                        <span class="text-muted">No backups</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $agent->backups()->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.agents.backups', $agent->id) }}" class="btn btn-info" title="View Backups">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <form action="{{ route('admin.clients.agents.destroy', [$client->id, $agent->id]) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this agent?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Delete Agent">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> No agents registered for this client yet.
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Client Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Client Name:</strong> {{ $client->name }}</p>
                    <p><strong>Storage Server:</strong> {{ $client->storageServer->name }}</p>
                    <p><strong>Region:</strong> {{ $client->storageServer->region }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Quota:</strong> {{ $client->quota_gb }} GB</p>
                    <p><strong>Status:</strong>
                        @if($client->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </p>
                    <p><strong>Total Agents:</strong> {{ $agents->count() }}</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(function () {
    $('#client-agents-table').DataTable({
        responsive: true,
        autoWidth: false
    });
});
</script>
@stop
