@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
<h1>Dashboard</h1>
@stop

@section('content')
<div class="row">
    <!-- Clients -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 x-text="summary.clients">0</h3>
                <p>Clients</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Active Agents -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 x-text="summary.agents.active">0</h3>
                <p>Active Agents</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
        </div>
    </div>

    <!-- Offline Agents -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 x-text="summary.agents.offline">0</h3>
                <p>Offline Agents</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <!-- Storage Usage -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 x-text="summary.storage.used_gb + '/' + summary.storage.total_gb + ' GB'">0</h3>
                <p>Storage Usage</p>
            </div>
            <div class="icon">
                <i class="fas fa-hdd"></i>
            </div>
        </div>
    </div>
</div>
@stop
