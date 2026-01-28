@extends('adminlte::page')

@section('title', 'Agents')

@section('plugins.Datatables', true)

@section('content_header')
<h1>Agents</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Agent List</h3>
    </div>
    <div class="card-body">
        <table id="agents-table" class="table table-bordered table-striped" style="width: 100%">
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
<script>
$(function () {
    $('#agents-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
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
