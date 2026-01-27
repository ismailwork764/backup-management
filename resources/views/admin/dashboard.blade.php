@extends('adminlte::page')

@section('title', 'Dashboard')

@section('plugins.Sweetalert2', true)

@section('content_header')
<h1>Dashboard</h1>
@stop

@section('content')
<div class="row" x-data="dashboardData()" x-init="init()">
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

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 x-text="summary.failed_backups_24h">0</h3>
                <p>Failed Backups (24h)</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function dashboardData() {
    return {
        summary: {
            clients: 0,
            agents: {
                active: 0,
                offline: 0
            },
            storage: {
                total_gb: 0,
                used_gb: 0,
                usage_percent: 0
            },
            failed_backups_24h: 0,
            recent_alerts: []
        },
        refreshInterval: 10000, 
        refreshTimer: null,
        knownAlertIds: new Set(),

        init() {
            this.fetchSummary();
            this.refreshTimer = setInterval(() => {
                this.fetchSummary();
            }, this.refreshInterval);
        },

        async fetchSummary() {
            try {
                const response = await fetch('/api/dashboard/summary');
                if (response.ok) {
                    const data = await response.json();
                    this.summary = data;
                    
                    if (data.recent_alerts && data.recent_alerts.length > 0) {
                        data.recent_alerts.forEach(alert => {
                            if (!this.knownAlertIds.has(alert.id)) {
                                this.showNotification(alert);
                                this.knownAlertIds.add(alert.id);
                            }
                        });
                    }
                } else {
                    console.error('Failed to fetch dashboard summary');
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
            }
        },

        showNotification(alert) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                icon: 'error',
                title: 'System Alert',
                text: alert.message
            });
        },

        destroy() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
        }
    };
}
</script>
@stop
