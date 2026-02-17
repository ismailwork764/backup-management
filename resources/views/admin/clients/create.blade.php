@extends('adminlte::page')

@section('title', 'Create Client')

@section('content_header')
    <h1>Create Client</h1>
@stop

@section('content')
    <div class="card">
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

        <form method="POST" action="{{ route('admin.clients.store') }}">
            @csrf
            <div class="card-body">

                <div class="form-group">
                    <label>Client Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Storage Server</label>
                    <select name="storage_server_id" class="form-control" required>
                        @foreach($storageServers as $server)
                            <option value="{{ $server->id }}">
                                {{ $server->name }} ({{ $server->region }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Quota (GB)</label>
                    <input type="number" name="quota_gb" class="form-control" min="10" required>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="daily_backup_notifications_enabled" class="custom-control-input" id="daily_backup_notifications_enabled" value="1">
                        <label class="custom-control-label" for="daily_backup_notifications_enabled">
                            <strong>Enable Daily Backup Summary Email (4:00 AM)</strong>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notification Email</label>
                    <input type="email" name="notification_email" class="form-control" placeholder="client@example.com">
                    <small class="form-text text-muted">
                        Required when daily backup summary email is enabled.
                    </small>
                </div>

                <div class="form-group">
                    <label><strong>Access Protocols</strong></label>
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 1rem;">Select which protocols should be enabled for this client's subaccount</p>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="reachable_externally" class="custom-control-input" id="reachable_externally" checked>
                        <label class="custom-control-label" for="reachable_externally">
                            <strong>External Reachability</strong>
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="ssh_enabled" class="custom-control-input" id="ssh_enabled" checked>
                        <label class="custom-control-label" for="ssh_enabled">
                            <strong>Allow SSH</strong>
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="samba_enabled" class="custom-control-input" id="samba_enabled">
                        <label class="custom-control-label" for="samba_enabled">
                            <strong>Allow SMB</strong>
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="webdav_enabled" class="custom-control-input" id="webdav_enabled">
                        <label class="custom-control-label" for="webdav_enabled">
                            <strong>WebDAV Enabled</strong>
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="readonly" class="custom-control-input" id="readonly">
                        <label class="custom-control-label" for="readonly">
                            <strong>Read Only</strong>
                        </label>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Client
                </button>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@stop
