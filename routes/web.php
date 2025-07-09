<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PbcRequestController;
use App\Http\Controllers\PbcRequestItemController;
use App\Http\Controllers\PbcSubmissionController;
use App\Http\Controllers\PbcCommentController;
use App\Http\Controllers\PbcReminderController;
use App\Http\Controllers\PbcCategoryController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or login - FIXED VERSION
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
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

    // Messages - EXISTING ROUTES (KEPT AS-IS)
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

    // Upload Center - EXISTING ROUTES (KEPT AS-IS)
    Route::get('/upload-center', [PbcSubmissionController::class, 'index'])->name('upload-center');

    // Settings routes - EXISTING ROUTES (KEPT AS-IS)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/get', [SettingsController::class, 'getSettings'])->name('settings.get');
    Route::post('/settings/update', [SettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/reset', [SettingsController::class, 'resetToDefaults'])->name('settings.reset');

    // User Management - EXISTING ROUTES (KEPT AS-IS)
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

    // Client Management - EXISTING ROUTES (KEPT AS-IS)
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

    // Project Management - EXISTING ROUTES (KEPT AS-IS)
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
    });

// PBC REQUEST MANAGEMENT - UI ONLY ROUTES
Route::prefix('pbc-requests')->group(function () {
    // Main PBC Request Index (Staff perspective)
    Route::get('/', function () {
        return view('pbc-requests.index');
    })->name('pbc-requests.index');
});

Route::prefix('pbc-templates')->group(function () {
    Route::get('/', function () {
        return view('pbc-templates.index');
    })->name('pbc-templates.index');

    Route::get('/create', function () {
        return view('pbc-templates.create-template');
    })->name('pbc-templates.create');

    Route::get('/edit', function () {
        return view('pbc-templates.edit-template');
    })->name('pbc-templates.edit');
});

// PROGRESS TRACKER - UI ONLY ROUTES
Route::prefix('progress')->group(function () {
    Route::get('/', function () {
        return view('progress.index');
    })->name('progress.index');

    Route::get('/modal', function () {
        return view('progress.progress-modal');
    })->name('progress.modal');
});


// DOCUMENT ARCHIVE - UI ONLY ROUTES
Route::prefix('document')->group(function () {
    Route::get('/', function () {
        return view('document.index');
    })->name('document.index');
});
