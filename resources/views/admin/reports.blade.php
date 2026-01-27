@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
<h1>Reports</h1>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css">
@stop

@section('content')
<ul class="nav nav-tabs" id="reportTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="storage-tab" data-toggle="tab" href="#storage" role="tab">
            <i class="fas fa-hdd"></i> Storage Utilization
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="monthly-tab" data-toggle="tab" href="#monthly" role="tab">
            <i class="fas fa-calendar-alt"></i> Monthly History
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="backups-tab" data-toggle="tab" href="#backups" role="tab">
            <i class="fas fa-database"></i> Backup Summary
        </a>
    </li>
</ul>

<div class="tab-content mt-3" id="reportTabsContent">
    <div class="tab-pane fade show active" id="storage" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Current Storage Utilization per Client</h3>
            </div>
            <div class="card-body">
                <table id="storage-table" class="table table-bordered table-striped" style="width: 100%">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Storage Server</th>
                            <th>Used (GB)</th>
                            <th>Quota (GB)</th>
                            <th>Available (GB)</th>
                            <th>Usage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="monthly" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Monthly Usage History (Highest GB per Month)</h3>
            </div>
            <div class="card-body">
                <table id="monthly-table" class="table table-bordered table-striped" style="width: 100%">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Month</th>
                            <th>Max Used (GB)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="backups" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Client Backup Summary</h3>
            </div>
            <div class="card-body">
                <table id="backups-table" class="table table-bordered table-striped" style="width: 100%">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Month</th>
                            <th>Total Backups</th>
                            <th>Failed Backups</th>
                            <th>Total Size (GB)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>

<script>
$(function () {
    $('#storage-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/reports/storage-utilization',
        columns: [
            { data: 'client_name' },
            { data: 'storage_server' },
            { data: 'used_gb' },
            { data: 'quota_gb' },
            { data: 'available_gb' },
            { data: 'usage_bar', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        width: '100%'
    });

    $('#monthly-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/reports/monthly-usage',
        columns: [
            { data: 'client_name' },
            { data: 'month_name' },
            { data: 'max_used_gb' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        width: '100%'
    });

    $('#backups-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/admin/api/reports',
        columns: [
            { data: 'client_name' },
            { data: 'month' },
            { data: 'total_backups' },
            { data: 'failed_count' },
            { data: 'total_gb' }
        ],
        order: [[1, 'desc']],
        width: '100%'
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    });
});
</script>
@stop
