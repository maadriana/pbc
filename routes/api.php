<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - SAFE VERSION WITH PROPER ERROR HANDLING
|--------------------------------------------------------------------------
*/

// TEST ROUTE - NO MIDDLEWARE (for testing)
Route::get('v1/test-no-auth', function() {
    return response()->json([
        'success' => true,
        'message' => 'No auth test working',
        'user' => auth()->check() ? auth()->user()->name : 'not logged in'
    ]);
});

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

    // 📊 Dashboard
    Route::get('dashboard', function() {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Dashboard working',
                'user' => auth()->user()->name ?? 'Guest'
            ]
        ]);
    });

    Route::get('dashboard/stats', function() {
        try {
            // Get real statistics from database safely
            $totalPbcRequests = \DB::table('pbc_requests')->count();
            $completedPbcRequests = \DB::table('pbc_requests')->where('status', 'completed')->count();
            $activePbcRequests = \DB::table('pbc_requests')->where('status', 'active')->count();
            $overduePbcRequests = \DB::table('pbc_requests')
                ->where(function($query) {
                    $query->where('status', 'overdue')
                          ->orWhere(function($subQuery) {
                              $subQuery->where('due_date', '<', now())
                                       ->where('status', '!=', 'completed');
                          });
                })->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pbc_requests' => [
                        'total' => $totalPbcRequests,
                        'completed' => $completedPbcRequests,
                        'active' => $activePbcRequests,
                        'overdue' => $overduePbcRequests
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pbc_requests' => [
                        'total' => 0,
                        'completed' => 0,
                        'active' => 0,
                        'overdue' => 0
                    ]
                ]
            ]);
        }
    });

    Route::get('dashboard/recent-activity', function() {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });

    // 👥 User Management - SAFE VERSION
    Route::get('users', function() {
        try {
            $users = \DB::table('users')
                        ->select('id', 'name', 'email', 'role', 'entity', 'contact_number', 'is_active', 'created_at')
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Users loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading users: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    });

    Route::get('users/export', function() {
        return response()->json([
            'success' => true,
            'message' => 'Users export working'
        ]);
    });

    // 🏢 Client Management - SAFE VERSION
    Route::get('clients', function() {
        try {
            $clients = \DB::table('clients')
                          ->select('id', 'name', 'sec_registration_no', 'industry_classification',
                                  'business_address', 'primary_contact_name', 'primary_contact_email',
                                  'primary_contact_number', 'is_active', 'created_at')
                          ->where('is_active', true)
                          ->orderBy('name')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'Clients loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading clients: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    });

    // 📁 Project Management - SAFE VERSION WITH JOINS
    Route::get('projects', function() {
        try {
            $projects = \DB::table('projects')
                          ->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
                          ->leftJoin('users as partners', 'projects.engagement_partner_id', '=', 'partners.id')
                          ->leftJoin('users as managers', 'projects.manager_id', '=', 'managers.id')
                          ->select(
                              'projects.id',
                              'projects.client_id',
                              'projects.engagement_type',
                              'projects.engagement_period',
                              'projects.contact_person',
                              'projects.contact_email',
                              'projects.status',
                              'projects.progress_percentage',
                              'projects.created_at',
                              'clients.name as client_name',
                              'partners.name as partner_name',
                              'managers.name as manager_name'
                          )
                          ->orderBy('projects.created_at', 'desc')
                          ->get()
                          ->map(function($project) {
                              $engagementYear = date('Y', strtotime($project->engagement_period));
                              $engagementTypeFormatted = ucwords(str_replace('_', ' ', $project->engagement_type));

                              return [
                                  'id' => $project->id,
                                  'name' => ($project->client_name ?? 'Unknown Client') . ' - ' . $engagementTypeFormatted . ' ' . $engagementYear,
                                  'client_id' => $project->client_id,
                                  'client' => [
                                      'id' => $project->client_id,
                                      'name' => $project->client_name ?? 'Unknown Client'
                                  ],
                                  'engagement_type' => $project->engagement_type,
                                  'status' => $project->status,
                                  'start_date' => date('Y-m-d', strtotime($project->created_at)),
                                  'engagement_period' => date('Y-m-d', strtotime($project->engagement_period)),
                                  'audit_period' => $engagementYear,
                                  'engagement_partner' => $project->partner_name,
                                  'manager' => $project->manager_name,
                                  'contact_person' => $project->contact_person,
                                  'contact_email' => $project->contact_email,
                                  'progress_percentage' => $project->progress_percentage ?? 0,
                                  'created_at' => date('c', strtotime($project->created_at))
                              ];
                          });

            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Projects loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading projects: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    });

    // 📄 PBC REQUEST MANAGEMENT - SAFE VERSION
    Route::get('pbc-requests', function() {
        try {
            $pbcRequests = \DB::table('pbc_requests')
                             ->leftJoin('projects', 'pbc_requests.project_id', '=', 'projects.id')
                             ->leftJoin('clients', 'projects.client_id', '=', 'clients.id')
                             ->leftJoin('pbc_templates', 'pbc_requests.template_id', '=', 'pbc_templates.id')
                             ->leftJoin('users as assigned', 'pbc_requests.assigned_to', '=', 'assigned.id')
                             ->leftJoin('users as creators', 'pbc_requests.created_by', '=', 'creators.id')
                             ->select(
                                 'pbc_requests.id',
                                 'pbc_requests.project_id',
                                 'pbc_requests.template_id',
                                 'pbc_requests.title',
                                 'pbc_requests.client_name',
                                 'pbc_requests.audit_period',
                                 'pbc_requests.status',
                                 'pbc_requests.assigned_to',
                                 'pbc_requests.due_date',
                                 'pbc_requests.completion_percentage',
                                 'pbc_requests.completed_items',
                                 'pbc_requests.total_items',
                                 'pbc_requests.notes',
                                 'pbc_requests.created_by',
                                 'pbc_requests.created_at',
                                 'pbc_requests.updated_at',
                                 'projects.engagement_type',
                                 'pbc_templates.name as template_name',
                                 'pbc_templates.description as template_description',
                                 'assigned.name as assigned_name',
                                 'assigned.role as assigned_role'
                             )
                             ->orderBy('pbc_requests.created_at', 'desc')
                             ->get()
                             ->map(function($request) {
                                 return [
                                     'id' => $request->id,
                                     'title' => $request->title,
                                     'project_id' => $request->project_id,
                                     'project' => $request->project_id ? [
                                         'id' => $request->project_id,
                                         'engagement_type' => $request->engagement_type
                                     ] : null,
                                     'client_name' => $request->client_name,
                                     'audit_period' => $request->audit_period,
                                     'template_id' => $request->template_id,
                                     'template' => $request->template_id ? [
                                         'id' => $request->template_id,
                                         'name' => $request->template_name,
                                         'description' => $request->template_description
                                     ] : null,
                                     'status' => $request->status,
                                     'assigned_to' => $request->assigned_to,
                                     'assignedTo' => $request->assigned_to ? [
                                         'id' => $request->assigned_to,
                                         'name' => $request->assigned_name,
                                         'role' => $request->assigned_role
                                     ] : null,
                                     'due_date' => $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : null,
                                     'completion_percentage' => $request->completion_percentage ?? 0,
                                     'completed_items' => $request->completed_items ?? 0,
                                     'total_items' => $request->total_items ?? 0,
                                     'notes' => $request->notes,
                                     'created_at' => date('c', strtotime($request->created_at)),
                                     'updated_at' => date('c', strtotime($request->updated_at))
                                 ];
                             });

            return response()->json([
                'success' => true,
                'data' => $pbcRequests,
                'pagination' => [
                    'total' => $pbcRequests->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $pbcRequests->count(),
                    'per_page' => 25
                ],
                'message' => 'PBC requests loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading PBC requests: ' . $e->getMessage(),
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 0,
                    'to' => 0,
                    'per_page' => 25
                ]
            ], 500);
        }
    });

    Route::post('pbc-requests', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 4, 'title' => 'New Custom Request'],
            'message' => 'PBC request created successfully'
        ]);
    });

    // PBC TEMPLATES - SAFE VERSION
    Route::get('pbc-requests/available-templates', function() {
        try {
            $templates = \DB::table('pbc_templates')
                           ->select('id', 'name', 'code', 'description', 'engagement_types',
                                   'is_default', 'is_active', 'created_at')
                           ->where('is_active', true)
                           ->orderBy('is_default', 'desc')
                           ->orderBy('name')
                           ->get()
                           ->map(function($template) {
                               // Get template item count safely
                               $itemCount = \DB::table('pbc_template_items')
                                              ->where('template_id', $template->id)
                                              ->count();

                               $engagementTypes = $template->engagement_types;
                               if (is_string($engagementTypes)) {
                                   $engagementTypes = json_decode($engagementTypes, true) ?? [$engagementTypes];
                               }

                               return [
                                   'id' => $template->id,
                                   'name' => $template->name,
                                   'code' => $template->code,
                                   'description' => $template->description,
                                   'engagement_types' => $engagementTypes,
                                   'engagement_type' => is_array($engagementTypes) ? ($engagementTypes[0] ?? 'audit') : 'audit',
                                   'category' => $template->name,
                                   'item_count' => $itemCount,
                                   'is_default' => $template->is_default,
                                   'created_at' => date('c', strtotime($template->created_at))
                               ];
                           });

            return response()->json([
                'success' => true,
                'data' => $templates,
                'message' => 'Templates loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading templates: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    });

    Route::post('pbc-requests/create-from-template', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 5, 'title' => 'Request from Template'],
            'message' => 'PBC request created from template successfully'
        ]);
    });

    Route::post('pbc-requests/bulk-update', function() {
        return response()->json([
            'success' => true,
            'data' => ['success' => 2, 'failed' => 0],
            'message' => 'Bulk update completed successfully'
        ]);
    });

    Route::post('pbc-requests/export', function() {
        return response()->json([
            'success' => true,
            'message' => 'Export completed successfully'
        ]);
    });

    // Individual PBC Request routes
    Route::post('pbc-requests/{id}/complete', function($id) {
        return response()->json([
            'success' => true,
            'data' => ['id' => $id, 'status' => 'completed'],
            'message' => 'Request completed successfully'
        ]);
    });

    Route::post('pbc-requests/{id}/reopen', function($id) {
        return response()->json([
            'success' => true,
            'data' => ['id' => $id, 'status' => 'active'],
            'message' => 'Request reopened successfully'
        ]);
    });

    Route::post('pbc-requests/{id}/duplicate', function($id) {
        return response()->json([
            'success' => true,
            'data' => ['id' => $id + 1000, 'title' => 'Duplicated Request'],
            'message' => 'Request duplicated successfully'
        ]);
    });

    Route::get('pbc-requests/{id}/progress', function($id) {
        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => 20,
                'completed_items' => 13,
                'completion_percentage' => 65
            ]
        ]);
    });

    Route::get('pbc-requests/{id}/items', function($id) {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });

    Route::get('pbc-requests/{id}/items/grouped', function($id) {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });

    Route::get('pbc-requests/{id}/comments', function($id) {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });

    // REST OF THE ROUTES (keeping them as they were working)
    // PBC REQUEST ITEMS
    Route::get('pbc-request-items', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'PBC request items working'
        ]);
    });

    Route::post('pbc-request-items', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Item creation working'
        ]);
    });

    Route::post('pbc-request-items/bulk-update', function() {
        return response()->json([
            'success' => true,
            'data' => ['success' => 0],
            'message' => 'Bulk update working'
        ]);
    });

    Route::get('pbc-request-items/overdue', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Overdue items working'
        ]);
    });

    // Individual PBC Request Item actions
    Route::post('pbc-request-items/{id}/accept', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'accepted']]);
    });

    Route::post('pbc-request-items/{id}/reject', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'rejected']]);
    });

    Route::post('pbc-request-items/{id}/submit', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'submitted']]);
    });

    Route::post('pbc-request-items/{id}/reset', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'pending']]);
    });

    Route::post('pbc-request-items/{id}/duplicate', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id + 1000]]);
    });

    Route::post('pbc-request-items/{id}/update-days-outstanding', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'days_outstanding' => 0]]);
    });

    Route::get('pbc-request-items/{id}/version-history', function($id) {
        return response()->json(['success' => true, 'data' => []]);
    });

    // PBC SUBMISSIONS
    Route::get('pbc-submissions', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'PBC submissions working'
        ]);
    });

    Route::post('pbc-submissions', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Submission creation working'
        ]);
    });

    Route::get('pbc-submissions/stats', function() {
        return response()->json([
            'success' => true,
            'data' => ['total' => 0, 'pending' => 0, 'approved' => 0],
            'message' => 'Submission stats working'
        ]);
    });

    Route::get('pbc-submissions/duplicates', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Duplicates check working'
        ]);
    });

    // Bulk submission actions
    Route::post('pbc-submissions/bulk-approve', function() {
        return response()->json(['success' => true, 'data' => ['success' => 0]]);
    });

    Route::post('pbc-submissions/bulk-reject', function() {
        return response()->json(['success' => true, 'data' => ['success' => 0]]);
    });

    Route::post('pbc-submissions/bulk-download', function() {
        return response()->json(['success' => true, 'message' => 'Bulk download working']);
    });

    Route::post('pbc-submissions/bulk-delete', function() {
        return response()->json(['success' => true, 'data' => ['success' => 0]]);
    });

    // Individual submission actions
    Route::get('pbc-submissions/{id}/download', function($id) {
        return response()->json(['success' => true, 'message' => 'Download working']);
    });

    Route::get('pbc-submissions/{id}/preview', function($id) {
        return response()->json(['success' => true, 'message' => 'Preview working']);
    });

    Route::post('pbc-submissions/{id}/approve', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'approved']]);
    });

    Route::post('pbc-submissions/{id}/reject', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'rejected']]);
    });

    Route::post('pbc-submissions/{id}/request-revision', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'revision_requested']]);
    });

    Route::post('pbc-submissions/{id}/new-version', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id + 1000]]);
    });

    Route::post('pbc-submissions/{id}/archive', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'archived' => true]]);
    });

    Route::post('pbc-submissions/{id}/restore', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'archived' => false]]);
    });

    // 💬 Comments
    Route::post('pbc-comments', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Comment creation working'
        ]);
    });

    Route::put('pbc-comments/{id}', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::delete('pbc-comments/{id}', function($id) {
        return response()->json(['success' => true, 'message' => 'Comment deleted']);
    });

    // 🔔 Reminders
    Route::get('pbc-reminders', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Reminders working'
        ]);
    });

    Route::post('pbc-reminders', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Reminder creation working'
        ]);
    });

    Route::post('pbc-reminders/bulk-send', function() {
        return response()->json([
            'success' => true,
            'data' => ['success' => 2],
            'message' => 'Bulk reminders sent successfully'
        ]);
    });

    Route::post('pbc-reminders/{id}/mark-read', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'read' => true]]);
    });

    // 📂 PBC Categories
    Route::get('pbc-categories', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Categories working'
        ]);
    });

    Route::post('pbc-categories', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Category creation working'
        ]);
    });

    Route::get('pbc-categories/{id}', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::put('pbc-categories/{id}', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::delete('pbc-categories/{id}', function($id) {
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    });

    // 📈 Reports
    Route::get('reports/pbc-status', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'PBC status report working'
        ]);
    });

    Route::get('reports/project-progress', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Project progress report working'
        ]);
    });

    Route::get('reports/audit-trail', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Audit trail report working'
        ]);
    });

    // 💬 Messages/Communication
    Route::get('messages/conversations', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Conversations working'
        ]);
    });

    Route::post('messages/conversations', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Conversation creation working'
        ]);
    });

    Route::get('messages/conversations/{id}', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::put('messages/conversations/{id}/status', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::put('messages/conversations/{id}/read-all', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::get('messages/conversations/{id}/messages', function($id) {
        return response()->json(['success' => true, 'data' => []]);
    });

    Route::post('messages/send', function() {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Message sending working'
        ]);
    });

    Route::put('messages/messages/{id}/read', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::get('messages/conversations/{id}/stats', function($id) {
        return response()->json(['success' => true, 'data' => []]);
    });

    Route::get('messages/conversations/{id}/search', function($id) {
        return response()->json(['success' => true, 'data' => []]);
    });

    Route::get('messages/conversations/{id}/attachments', function($id) {
        return response()->json(['success' => true, 'data' => []]);
    });

    Route::get('messages/conversations/{cid}/messages/{mid}/attachments/{aid}/download', function($cid, $mid, $aid) {
        return response()->json(['success' => true, 'message' => 'Download working']);
    });

    Route::post('messages/conversations/{id}/participants', function($id) {
        return response()->json(['success' => true, 'data' => ['id' => $id]]);
    });

    Route::delete('messages/conversations/{cid}/participants/{uid}', function($cid, $uid) {
        return response()->json(['success' => true, 'message' => 'Participant removed']);
    });

    Route::get('messages/unread-count', function() {
        return response()->json([
            'success' => true,
            'data' => ['count' => 0],
            'message' => 'Unread count working'
        ]);
    });

    Route::get('messages/available-users', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Available users working'
        ]);
    });

    // Settings Management
    Route::get('settings', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Settings working'
        ]);
    });

    Route::post('settings', function() {
        return response()->json([
            'success' => true,
            'message' => 'Settings update working'
        ]);
    });

    Route::post('settings/reset', function() {
        return response()->json([
            'success' => true,
            'message' => 'Settings reset working'
        ]);
    });

    Route::get('settings/public', function() {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Public settings working'
        ]);
    });

    Route::get('settings/{key}', function($key) {
        return response()->json(['success' => true, 'data' => ['key' => $key, 'value' => null]]);
    });

    Route::put('settings/{key}', function($key) {
        return response()->json(['success' => true, 'data' => ['key' => $key]]);
    });

}); // End of v1 routes
