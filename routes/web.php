<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PbcRequestController;
use App\Http\Controllers\PbcDocumentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SettingsController;

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

// Test API route - Public for testing
Route::get('/test-api', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => now()
    ]);
});

// Protected routes
Route::middleware(['auth:web'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    // Messages routes (Web endpoints for AJAX calls)
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/conversations', [MessageController::class, 'getConversations'])->name('messages.conversations');
    Route::get('/messages/users', [MessageController::class, 'getAvailableUsers'])->name('messages.users');
    Route::get('/messages/clients', [MessageController::class, 'getClients'])->name('messages.clients');
    Route::get('/messages/projects', [MessageController::class, 'getProjects'])->name('messages.projects');
    Route::get('/messages/conversations/{id}', [MessageController::class, 'getConversation'])->name('messages.conversation');
    Route::get('/messages/conversations/{id}/messages', [MessageController::class, 'getMessages'])->name('messages.conversation.messages');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('messages.send');
    Route::post('/messages/conversations', [MessageController::class, 'createConversation'])->name('messages.create-conversation');
    Route::put('/messages/conversations/{id}/read', [MessageController::class, 'markConversationAsRead'])->name('messages.mark-read');
    // Upload Center
    Route::get('/upload-center', [PbcDocumentController::class, 'uploadCenterPage'])->name('upload-center');

    // Settings routes (Web endpoints for AJAX calls)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/get', [SettingsController::class, 'getSettings'])->name('settings.get');
    Route::post('/settings/update', [SettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/reset', [SettingsController::class, 'resetToDefaults'])->name('settings.reset');

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
});
