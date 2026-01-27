@extends('adminlte::page')

@section('title', 'Clients')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Clients</h1>
        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Client
        </a>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Client List</h3>
    </div>
    <div class="card-body">
        <table id="clients-table" class="table table-bordered table-striped" style="width: 100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Registration Key</th>
                    <th>Storage Server</th>
                    <th>Agents</th>
                    <th>Last Backup</th>
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
    $('#clients-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/clients',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'registration_key' },
            { data: 'storage_server' },
            { data: 'agents_count' },
            { data: 'last_backup' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@stop
