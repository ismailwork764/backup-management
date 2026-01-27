@extends('adminlte::page')

@section('title', 'Storage Servers')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Storage Servers</h1>
    <div>
        <a href="{{ route('admin.storage_servers.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Create Storage Box
        </a>
        <form action="{{ route('admin.storage_servers.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sync"></i> Sync from Hetzner
            </button>
        </form>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">
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
        <h3 class="card-title">All Storage Servers</h3>
    </div>
    <div class="card-body">
        <table id="servers-table" class="table table-bordered table-striped" style="width: 100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Region</th>
                    <th>Usage</th>
                    <th>Clients</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>

<script>
$(function () {
    $('#servers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/storage-servers',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'server_address' },
            { data: 'region' },
            { data: 'usage', orderable: false, searchable: false },
            { data: 'clients_count' },
            { data: 'status_badge', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@stop
