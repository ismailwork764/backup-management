@extends('adminlte::page')

@section('title', 'Agent Backups')

@section('content_header')
<h1>Backups for Agent: {{ $agent->hostname }}</h1>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Backups</h3>
    </div>
    <div class="card-body">
        <table id="backups-table" class="table table-bordered table-striped" style="width: 100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Size (GB)</th>
                    <th>Created At</th>
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
    $('#backups-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/agents/{{ $agent->id }}/backups',
        columns: [
            { data: 'id' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'message' },
            { data: 'size_gb' },
            { data: 'created_at' }
        ]
    });
});
</script>
@stop
