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

    // PBC REQUEST MANAGEMENT - COMPLETE UPDATED SECTION
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
    Route::prefix('pbc-requests')->group(function () {
        Route::get('create-from-template', [PbcRequestController::class, 'createFromTemplate'])->name('pbc-requests.create-from-template');
        Route::post('create-from-template', [PbcRequestController::class, 'storeFromTemplate'])->name('pbc-requests.store-from-template');
        Route::get('available-templates', [PbcRequestController::class, 'getAvailableTemplates'])->name('pbc-requests.available-templates');
        Route::post('bulk-update', [PbcRequestController::class, 'bulkUpdate'])->name('pbc-requests.bulk-update');
        Route::get('export', [PbcRequestController::class, 'export'])->name('pbc-requests.export');
    });

    // Individual PBC Request routes
    Route::prefix('pbc-requests/{pbcRequest}')->group(function () {
        Route::post('complete', [PbcRequestController::class, 'complete'])->name('pbc-requests.complete');
        Route::post('reopen', [PbcRequestController::class, 'reopen'])->name('pbc-requests.reopen');
        Route::post('duplicate', [PbcRequestController::class, 'duplicate'])->name('pbc-requests.duplicate');
        Route::get('progress', [PbcRequestController::class, 'getProgress'])->name('pbc-requests.progress');
        Route::get('items', [PbcRequestItemController::class, 'getByRequest'])->name('pbc-requests.items');
        Route::get('items/grouped', [PbcRequestItemController::class, 'getGroupedByCategory'])->name('pbc-requests.items.grouped');
    });

    // PBC REQUEST ITEMS - NEW COMPLETE SECTION
    Route::resource('pbc-request-items', PbcRequestItemController::class)->names([
        'index' => 'pbc-request-items.index',
        'create' => 'pbc-request-items.create',
        'store' => 'pbc-request-items.store',
        'show' => 'pbc-request-items.show',
        'edit' => 'pbc-request-items.edit',
        'update' => 'pbc-request-items.update',
        'destroy' => 'pbc-request-items.destroy'
    ]);

    // PBC Request Item additional routes
    Route::prefix('pbc-request-items')->group(function () {
        Route::post('bulk-update', [PbcRequestItemController::class, 'bulkUpdate'])->name('pbc-request-items.bulk-update');
        Route::get('overdue', [PbcRequestItemController::class, 'getOverdueItems'])->name('pbc-request-items.overdue');
    });

    // Individual PBC Request Item actions
    Route::prefix('pbc-request-items/{pbcRequestItem}')->group(function () {
        Route::post('accept', [PbcRequestItemController::class, 'accept'])->name('pbc-request-items.accept');
        Route::post('reject', [PbcRequestItemController::class, 'reject'])->name('pbc-request-items.reject');
        Route::post('submit', [PbcRequestItemController::class, 'submit'])->name('pbc-request-items.submit');
        Route::post('reset', [PbcRequestItemController::class, 'resetToPending'])->name('pbc-request-items.reset');
        Route::post('duplicate', [PbcRequestItemController::class, 'duplicate'])->name('pbc-request-items.duplicate');
        Route::post('update-days-outstanding', [PbcRequestItemController::class, 'updateDaysOutstanding'])->name('pbc-request-items.update-days-outstanding');
    });

    // PBC SUBMISSIONS (DOCUMENTS) - NEW COMPLETE SECTION
    Route::resource('pbc-submissions', PbcSubmissionController::class)->names([
        'index' => 'pbc-submissions.index',
        'create' => 'pbc-submissions.create',
        'store' => 'pbc-submissions.store',
        'show' => 'pbc-submissions.show',
        'edit' => 'pbc-submissions.edit',
        'update' => 'pbc-submissions.update',
        'destroy' => 'pbc-submissions.destroy'
    ]);

    // PBC Submission bulk operations
    Route::prefix('pbc-submissions')->group(function () {
        Route::post('bulk-approve', [PbcSubmissionController::class, 'bulkApprove'])->name('pbc-submissions.bulk-approve');
        Route::post('bulk-reject', [PbcSubmissionController::class, 'bulkReject'])->name('pbc-submissions.bulk-reject');
        Route::post('bulk-download', [PbcSubmissionController::class, 'bulkDownload'])->name('pbc-submissions.bulk-download');
        Route::post('bulk-delete', [PbcSubmissionController::class, 'bulkDelete'])->name('pbc-submissions.bulk-delete');
        Route::get('stats', [PbcSubmissionController::class, 'getStats'])->name('pbc-submissions.stats');
        Route::get('duplicates', [PbcSubmissionController::class, 'getDuplicates'])->name('pbc-submissions.duplicates');
    });

    // Individual PBC Submission actions
    Route::prefix('pbc-submissions/{pbcSubmission}')->group(function () {
        Route::get('download', [PbcSubmissionController::class, 'download'])->name('pbc-submissions.download');
        Route::get('preview', [PbcSubmissionController::class, 'preview'])->name('pbc-submissions.preview');
        Route::post('approve', [PbcSubmissionController::class, 'approve'])->name('pbc-submissions.approve');
        Route::post('reject', [PbcSubmissionController::class, 'reject'])->name('pbc-submissions.reject');
        Route::post('request-revision', [PbcSubmissionController::class, 'requestRevision'])->name('pbc-submissions.request-revision');
        Route::post('new-version', [PbcSubmissionController::class, 'createNewVersion'])->name('pbc-submissions.new-version');
        Route::post('archive', [PbcSubmissionController::class, 'archive'])->name('pbc-submissions.archive');
        Route::post('restore', [PbcSubmissionController::class, 'restore'])->name('pbc-submissions.restore');
        Route::get('version-history', [PbcSubmissionController::class, 'getVersionHistory'])->name('pbc-submissions.version-history');
    });

    // PBC CATEGORIES - NEW SECTION
    Route::resource('pbc-categories', PbcCategoryController::class)->names([
        'index' => 'pbc-categories.index',
        'create' => 'pbc-categories.create',
        'store' => 'pbc-categories.store',
        'show' => 'pbc-categories.show',
        'edit' => 'pbc-categories.edit',
        'update' => 'pbc-categories.update',
        'destroy' => 'pbc-categories.destroy'
    ]);

    // PBC COMMENTS - NEW SECTION
    Route::prefix('pbc-comments')->group(function () {
        Route::post('/', [PbcCommentController::class, 'store'])->name('pbc-comments.store');
        Route::put('{comment}', [PbcCommentController::class, 'update'])->name('pbc-comments.update');
        Route::delete('{comment}', [PbcCommentController::class, 'destroy'])->name('pbc-comments.destroy');
    });

    // PBC comments for specific requests
    Route::get('pbc-requests/{pbcRequest}/comments', [PbcCommentController::class, 'index'])->name('pbc-requests.comments');

    // PBC REMINDERS - NEW SECTION
    Route::resource('pbc-reminders', PbcReminderController::class)->names([
        'index' => 'pbc-reminders.index',
        'create' => 'pbc-reminders.create',
        'store' => 'pbc-reminders.store',
        'show' => 'pbc-reminders.show',
        'edit' => 'pbc-reminders.edit',
        'update' => 'pbc-reminders.update',
        'destroy' => 'pbc-reminders.destroy'
    ]);

    // PBC Reminder additional routes
    Route::prefix('pbc-reminders')->group(function () {
        Route::post('bulk-send', [PbcReminderController::class, 'bulkSend'])->name('pbc-reminders.bulk-send');
    });

    Route::prefix('pbc-reminders/{reminder}')->group(function () {
        Route::post('mark-read', [PbcReminderController::class, 'markAsRead'])->name('pbc-reminders.mark-read');
        Route::post('cancel', [PbcReminderController::class, 'cancel'])->name('pbc-reminders.cancel');
        Route::post('reschedule', [PbcReminderController::class, 'reschedule'])->name('pbc-reminders.reschedule');
    });
});

// Public file download routes (with authentication in controller)
Route::get('files/pbc-submissions/{pbcSubmission}/download', [PbcSubmissionController::class, 'download'])->name('files.pbc-submissions.download');
Route::get('files/pbc-submissions/{pbcSubmission}/preview', [PbcSubmissionController::class, 'preview'])->name('files.pbc-submissions.preview');
