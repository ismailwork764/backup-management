<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\StorageServerController;
use App\Http\Controllers\Admin\ReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('welcome');
})->name('about');


Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('clients', [ClientController::class, 'index'])->name('admin.clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
    Route::post('clients', [ClientController::class, 'store'])->name('admin.clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('admin.clients.show');
    Route::get('clients/{client}/agents', [ClientController::class, 'agentsIndex'])->name('admin.clients.agents.index');
    Route::delete('clients/{client}/agents/{agent}', [ClientController::class, 'destroyAgent'])->name('admin.clients.agents.destroy');

    Route::get('api/clients', [ClientController::class, 'apiIndex']);
    Route::get('agents', [AgentController::class, 'index'])->name('admin.agents');
    Route::get('api/agents', [AgentController::class, 'apiIndex']);
    Route::get('agents/{agent}/backups', [AgentController::class, 'backups'])->name('admin.agents.backups');
    Route::get('api/agents/{agent}/backups', [AgentController::class, 'apiBackups']);
    Route::get('storage-servers', [StorageServerController::class, 'index'])->name('admin.storage_servers.index');
    Route::get('storage-servers/create', [StorageServerController::class, 'create'])->name('admin.storage_servers.create');
    Route::post('storage-servers', [StorageServerController::class, 'store'])->name('admin.storage_servers.store');
    Route::post('storage-servers/sync', [StorageServerController::class, 'sync'])->name('admin.storage_servers.sync');
    Route::get('storage-servers/{storageServer}', [StorageServerController::class, 'show'])->name('admin.storage_servers.show');
    Route::delete('storage-servers/{storageServer}', [StorageServerController::class, 'destroy'])->name('admin.storage_servers.destroy');
    Route::delete('storage-servers/{storageServer}/subaccounts/{client}', [StorageServerController::class, 'destroySubaccount'])->name('admin.storage_servers.subaccounts.destroy');
    Route::get('api/storage-servers', [StorageServerController::class, 'apiIndex']);
    Route::get('reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('api/reports', [ReportController::class, 'apiIndex']);
    Route::get('api/reports/storage-utilization', [ReportController::class, 'storageUtilization'])->name('admin.reports.storage-utilization');
    Route::get('api/reports/monthly-usage', [ReportController::class, 'monthlyUsage'])->name('admin.reports.monthly-usage');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
