@extends('adminlte::page')

@section('title', 'Storage Server Details')

@section('content_header')
    <h1>Storage Server: {{ $storageServer->name }}</h1>
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
            <h5>Server Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $storageServer->name }}</td>
                </tr>
                <tr>
                    <th>Region</th>
                    <td>{{ $storageServer->region }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if($storageServer->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Hetzner ID</th>
                    <td>{{ $storageServer->hetzner_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Storage Utilization</th>
                    <td>
                        @php
                            $percent = $storageServer->total_capacity_gb
                                ? round(($storageServer->used_capacity_gb / $storageServer->total_capacity_gb) * 100)
                                : 0;
                            $colorClass = $percent > 80 ? 'bg-danger' : ($percent > 60 ? 'bg-warning' : 'bg-success');
                        @endphp
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $colorClass }}"
                                 role="progressbar"
                                 style="width: {{ $percent }}%"
                                 aria-valuenow="{{ $percent }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ $storageServer->used_capacity_gb }} GB / {{ $storageServer->total_capacity_gb }} GB ({{ $percent }}%)
                            </div>
                        </div>
                        @if($percent > 80)
                            <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Warning: Storage is above 80% capacity</small>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Subaccounts (Clients) on this Server</h5>
        </div>
        <div class="card-body">
            @if($storageServer->clients->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Registration Key</th>
                            <th>Agents</th>
                            <th>Quota (GB)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($storageServer->clients as $client)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.clients.show', $client->id) }}">
                                        {{ $client->name }}
                                    </a>
                                </td>
                                <td>
                                    <code>{{ $client->registration_key }}</code>
                                </td>
                                <td>{{ $client->agents_count ?? 0 }}</td>
                                <td>{{ $client->quota_gb }} GB</td>
                                <td>
                                    @if($client->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.storage_servers.subaccounts.destroy', [$storageServer->id, $client->id]) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this subaccount and client? This will also delete all associated agents and backups. This action cannot be undone.');">
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
                <p class="text-muted">No clients/subaccounts on this storage server.</p>
            @endif
        </div>
    </div>

    <div class="card-footer mt-3">
        <a href="{{ route('admin.storage_servers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Storage Servers
        </a>
    </div>
@stop

