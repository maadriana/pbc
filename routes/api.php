<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PbcRequestController;
use App\Http\Controllers\PbcRequestItemController;
use App\Http\Controllers\PbcSubmissionController;
use App\Http\Controllers\PbcCommentController;
use App\Http\Controllers\PbcReminderController;
use App\Http\Controllers\PbcCategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // 🔓 Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);

        Route::middleware('auth:web')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        });
    });

    // 🔐 Protected routes
    Route::middleware(['auth:web'])->group(function () {

        // 📊 Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::get('recent-activity', [DashboardController::class, 'recentActivity']);
        });

        // 👥 User Management - Full API Resource
        Route::apiResource('users', UserController::class);
        Route::prefix('users/{user}')->group(function () {
            Route::get('permissions', [UserController::class, 'permissions']);
            Route::put('permissions', [UserController::class, 'updatePermissions']);
        });
        // Export route
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');

        // 🏢 Client Management
        Route::apiResource('clients', ClientController::class);
        Route::get('clients/{client}/projects', [ClientController::class, 'projects']);

        // 📁 Project Management
        Route::apiResource('projects', ProjectController::class);
        Route::prefix('projects/{project}')->group(function () {
            Route::get('pbc-requests', [ProjectController::class, 'pbcRequests']);
            Route::put('update-progress', [ProjectController::class, 'updateProgress']);
        });

        // 📄 PBC REQUEST MANAGEMENT - UPDATED COMPLETE SECTION
        Route::apiResource('pbc-requests', PbcRequestController::class, [
            'names' => [
                'index' => 'pbc-requests.index',
                'store' => 'pbc-requests.store',
                'show' => 'pbc-requests.show',
                'update' => 'pbc-requests.update',
                'destroy' => 'pbc-requests.destroy',
            ]
        ]);

        // Additional PBC Request routes
        Route::prefix('pbc-requests')->group(function () {
            Route::post('create-from-template', [PbcRequestController::class, 'createFromTemplate'])->name('pbc-requests.create-from-template');
            Route::post('bulk-update', [PbcRequestController::class, 'bulkUpdate'])->name('pbc-requests.bulk-update');
            Route::get('available-templates', [PbcRequestController::class, 'getAvailableTemplates'])->name('pbc-requests.available-templates');
            Route::post('export', [PbcRequestController::class, 'export'])->name('pbc-requests.export');
        });

        // PBC Request specific routes
        Route::prefix('pbc-requests/{pbcRequest}')->group(function () {
            Route::post('complete', [PbcRequestController::class, 'complete'])->name('pbc-requests.complete');
            Route::post('reopen', [PbcRequestController::class, 'reopen'])->name('pbc-requests.reopen');
            Route::post('duplicate', [PbcRequestController::class, 'duplicate'])->name('pbc-requests.duplicate');
            Route::get('progress', [PbcRequestController::class, 'getProgress'])->name('pbc-requests.progress');
            Route::get('items', [PbcRequestItemController::class, 'getByRequest'])->name('pbc-requests.items');
            Route::get('items/grouped', [PbcRequestItemController::class, 'getGroupedByCategory'])->name('pbc-requests.items.grouped');
            Route::get('comments', [PbcCommentController::class, 'index'])->name('pbc-requests.comments');
        });

        // PBC REQUEST ITEMS - NEW COMPLETE SECTION
        Route::apiResource('pbc-request-items', PbcRequestItemController::class, [
            'names' => [
                'index' => 'pbc-request-items.index',
                'store' => 'pbc-request-items.store',
                'show' => 'pbc-request-items.show',
                'update' => 'pbc-request-items.update',
                'destroy' => 'pbc-request-items.destroy',
            ]
        ]);

        // PBC Request Item actions
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
            Route::get('version-history', [PbcSubmissionController::class, 'getVersionHistory'])->name('pbc-request-items.version-history');
        });

        // PBC SUBMISSIONS (DOCUMENTS) - NEW COMPLETE SECTION
        Route::apiResource('pbc-submissions', PbcSubmissionController::class, [
            'names' => [
                'index' => 'pbc-submissions.index',
                'store' => 'pbc-submissions.store',
                'show' => 'pbc-submissions.show',
                'destroy' => 'pbc-submissions.destroy',
            ]
        ]);

        // PBC Submission actions
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
        });

        // 💬 Comments - UPDATED SECTION
        Route::apiResource('pbc-comments', PbcCommentController::class, [
            'names' => [
                'store' => 'pbc-comments.store',
                'update' => 'pbc-comments.update',
                'destroy' => 'pbc-comments.destroy',
            ]
        ])->except(['index', 'show']);

        // 🔔 Reminders - UPDATED SECTION
        Route::apiResource('pbc-reminders', PbcReminderController::class, [
            'names' => [
                'index' => 'pbc-reminders.index',
                'store' => 'pbc-reminders.store',
            ]
        ])->only(['index', 'store']);

        Route::prefix('pbc-reminders')->group(function () {
            Route::post('bulk-send', [PbcReminderController::class, 'bulkSend'])->name('pbc-reminders.bulk-send');
        });

        Route::prefix('pbc-reminders/{reminder}')->group(function () {
            Route::post('mark-read', [PbcReminderController::class, 'markAsRead'])->name('pbc-reminders.mark-read');
        });

        // 📂 PBC Categories
        Route::apiResource('pbc-categories', PbcCategoryController::class);

        // 📈 Reports
        Route::prefix('reports')->group(function () {
            Route::get('pbc-status', [ReportController::class, 'pbcStatus']);
            Route::get('project-progress', [ReportController::class, 'projectProgress']);
            Route::get('audit-trail', [ReportController::class, 'auditTrail']);
        });

        // 💬 Messages/Communication - COMPLETE ROUTES
        Route::prefix('messages')->group(function () {
            // Conversations
            Route::get('conversations', [MessageController::class, 'getConversations']);
            Route::post('conversations', [MessageController::class, 'createConversation']);
            Route::get('conversations/{conversation}', [MessageController::class, 'getConversation']);
            Route::put('conversations/{conversation}/status', [MessageController::class, 'updateConversationStatus']);
            Route::put('conversations/{conversation}/read-all', [MessageController::class, 'markConversationAsRead']);

            // Messages
            Route::get('conversations/{conversation}/messages', [MessageController::class, 'getMessages']);
            Route::post('send', [MessageController::class, 'sendMessage']);
            Route::put('messages/{message}/read', [MessageController::class, 'markAsRead']);

            // Advanced Features
            Route::get('conversations/{conversation}/stats', [MessageController::class, 'getConversationStats']);
            Route::get('conversations/{conversation}/search', [MessageController::class, 'searchMessages']);
            Route::get('conversations/{conversation}/attachments', [MessageController::class, 'getConversationAttachments']);
            Route::get('conversations/{conversation}/messages/{message}/attachments/{attachment}/download', [MessageController::class, 'downloadAttachment']);

            // Participant Management
            Route::post('conversations/{conversation}/participants', [MessageController::class, 'addParticipant']);
            Route::delete('conversations/{conversation}/participants/{user}', [MessageController::class, 'removeParticipant']);

            // Utilities
            Route::get('unread-count', [MessageController::class, 'getUnreadCount']);
            Route::get('available-users', [MessageController::class, 'getAvailableUsers']);
        });

        // Settings Management - add these lines
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'getSettings']);
            Route::post('/', [SettingsController::class, 'updateSettings']);
            Route::post('reset', [SettingsController::class, 'resetToDefaults']);
            Route::get('public', [SettingsController::class, 'getPublicSettings']);
            Route::get('{key}', [SettingsController::class, 'getSetting']);
            Route::put('{key}', [SettingsController::class, 'updateSetting']);
        });

    }); // End of protected routes

}); // 📦 End of /v1

