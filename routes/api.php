<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PbcRequestController;
use App\Http\Controllers\PbcDocumentController;
use App\Http\Controllers\PbcCommentController;
use App\Http\Controllers\PbcReminderController;
use App\Http\Controllers\PbcCategoryController;
use App\Http\Controllers\ReportController;

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
    Route::middleware(['auth:web', 'pbc.permission'])->group(function () {

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

        // 📄 PBC Request Management
        Route::apiResource('pbc-requests', PbcRequestController::class);
        Route::prefix('pbc-requests')->group(function () {
            Route::put('bulk-update', [PbcRequestController::class, 'bulkUpdate']);
        });
        Route::prefix('pbc-requests/{pbcRequest}')->group(function () {
            Route::put('complete', [PbcRequestController::class, 'complete']);
            Route::put('reopen', [PbcRequestController::class, 'reopen']);
        });

        // 📎 PBC Document Management
        Route::apiResource('pbc-documents', PbcDocumentController::class)->except(['update']);
        Route::prefix('pbc-documents/{document}')->group(function () {
            Route::get('download', [PbcDocumentController::class, 'download']);
            Route::get('preview', [PbcDocumentController::class, 'preview']);
            Route::put('approve', [PbcDocumentController::class, 'approve']);
            Route::put('reject', [PbcDocumentController::class, 'reject']);
        });

        // 💬 Comments
        Route::get('pbc-requests/{pbcRequest}/comments', [PbcCommentController::class, 'index']);
        Route::post('pbc-comments', [PbcCommentController::class, 'store']);
        Route::put('pbc-comments/{comment}', [PbcCommentController::class, 'update']);
        Route::delete('pbc-comments/{comment}', [PbcCommentController::class, 'destroy']);

        // 🔔 Reminders
        Route::apiResource('pbc-reminders', PbcReminderController::class)->only(['index', 'store']);
        Route::prefix('pbc-reminders')->group(function () {
            Route::put('bulk-send', [PbcReminderController::class, 'bulkSend']);
        });
        Route::put('pbc-reminders/{reminder}/mark-read', [PbcReminderController::class, 'markAsRead']);

        // 📂 PBC Categories
        Route::apiResource('pbc-categories', PbcCategoryController::class);

        // 📈 Reports
        Route::prefix('reports')->group(function () {
            Route::get('pbc-status', [ReportController::class, 'pbcStatus']);
            Route::get('project-progress', [ReportController::class, 'projectProgress']);
            Route::get('audit-trail', [ReportController::class, 'auditTrail']);
        });

        // 🧪 API health check
        Route::get('/test', function () {
            return response()->json([
                'success' => true,
                'message' => 'API is working',
                'timestamp' => now(),
                'user' => auth()->user() ? auth()->user()->name : 'Not authenticated',
                'laravel_version' => app()->version()
            ]);
        });

    }); // 🔐 End of protected routes

}); // 📦 End of /v1
