<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PbcRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or login
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('register', function () {
        return view('auth.register');
    })->name('register');

    // POST routes for authentication
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Logout route (needs to be outside guest middleware)
Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware(['auth', 'pbc.permission'])->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Management - Full Resource Routes
    Route::resource('users', UserController::class)->names([
        'index' => 'users.index',
        'create' => 'users.create',
        'store' => 'users.store',
        'show' => 'users.show',
        'edit' => 'users.edit',
        'update' => 'users.update',
        'destroy' => 'users.destroy'
    ]);

    // User permissions routes
    Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.update-permissions');

    // Alternative endpoint for backward compatibility
    Route::get('users/list', [UserController::class, 'index'])->name('users.list');

    // Client Management - Full Resource Routes
    Route::resource('clients', ClientController::class)->names([
        'index' => 'clients.index',
        'create' => 'clients.create',
        'store' => 'clients.store',
        'show' => 'clients.show',
        'edit' => 'clients.edit',
        'update' => 'clients.update',
        'destroy' => 'clients.destroy'
    ]);

    // Client additional routes
    Route::get('clients/{client}/projects', [ClientController::class, 'projects'])->name('clients.projects');
    Route::get('clients/export', [ClientController::class, 'export'])->name('clients.export');

    // Project Management - Full Resource Routes
    Route::resource('projects', ProjectController::class)->names([
        'index' => 'projects.index',
        'create' => 'projects.create',
        'store' => 'projects.store',
        'show' => 'projects.show',
        'edit' => 'projects.edit',
        'update' => 'projects.update',
        'destroy' => 'projects.destroy'
    ]);

    // Project additional routes
    Route::get('projects/{project}/pbc-requests', [ProjectController::class, 'pbcRequests'])->name('projects.pbc-requests');
    Route::put('projects/{project}/update-progress', [ProjectController::class, 'updateProgress'])->name('projects.update-progress');
    Route::get('projects/export', [ProjectController::class, 'export'])->name('projects.export');

        // PBC Request Management - Full Resource Routes
    Route::resource('pbc-requests', PbcRequestController::class)->names([
        'index' => 'pbc-requests.index',
        'create' => 'pbc-requests.create',
        'store' => 'pbc-requests.store',
        'show' => 'pbc-requests.show',
        'edit' => 'pbc-requests.edit',
        'update' => 'pbc-requests.update',
        'destroy' => 'pbc-requests.destroy'
    ]);

    // PBC Request additional routes
    Route::put('pbc-requests/{pbcRequest}/complete', [PbcRequestController::class, 'complete'])->name('pbc-requests.complete');
    Route::put('pbc-requests/{pbcRequest}/reopen', [PbcRequestController::class, 'reopen'])->name('pbc-requests.reopen');
    Route::put('pbc-requests/bulk-update', [PbcRequestController::class, 'bulkUpdate'])->name('pbc-requests.bulk-update');
    // Future routes for other pages - Keep these as view-only for now
    Route::get('pbc-requests', function () {
        return view('pbc-requests.index');
    })->name('pbc-requests.index');
});
