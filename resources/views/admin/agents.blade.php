@extends('adminlte::page')

@section('title', 'Agents')

@section('content_header')
<h1>Agents</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Agent List</h3>
    </div>
    <div class="card-body">
        <table id="agents-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hostname</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Last Seen</th>
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
    $('#agents-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/agents',
        columns: [
            { data: 'id' },
            { data: 'hostname' },
            { data: 'client_name' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'last_seen' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@stop
