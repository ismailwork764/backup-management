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
