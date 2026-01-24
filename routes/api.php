<?php
use App\Http\Controllers\Api\AgentRegistrationController;
use App\Http\Controllers\Api\AgentHeartbeatController;
use App\Http\Controllers\Api\BackupStatusController;
use App\Http\Middleware\AuthenticateAgent;
use App\Http\Controllers\Api\AdminReportingController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\StorageServerController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReportController;

Route::post('/register', [AgentRegistrationController::class, 'register']);
Route::get('/admin/clients-overview', [AdminReportingController::class, 'clientsOverview']);
Route::get('/admin/client/{clientId}/backup-history', [AdminReportingController::class, 'clientBackupHistory']);

Route::middleware('agent.auth')->group(function () {
    Route::post('/heartbeat', [AgentHeartbeatController::class, 'store']);
    Route::post('/backup-status', [BackupStatusController::class, 'store']);
});
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    });

Route::prefix('admin')->group(function () {

    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/storage-servers', [StorageServerController::class, 'index']);
    Route::get('/agents/{agent}/backups', [AgentController::class, 'backups']);
    Route::get('/reports/monthly-usage', [ReportController::class, 'monthlyUsage']);

});

