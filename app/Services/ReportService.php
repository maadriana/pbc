<?php

namespace App\Services;

use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get PBC status report data
     */
    public function getPbcStatusReport($filters = [])
    {
        $query = PbcRequest::with(['project.client', 'assignedTo', 'template']);

        // Apply filters
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $requests = $query->get();

        return [
            'total_requests' => $requests->count(),
            'completed' => $requests->where('status', 'completed')->count(),
            'active' => $requests->where('status', 'active')->count(),
            'draft' => $requests->where('status', 'draft')->count(),
            'overdue' => $requests->filter(function ($request) {
                return $request->due_date && Carbon::parse($request->due_date)->isPast() && $request->status !== 'completed';
            })->count(),
            'requests' => $requests,
            'completion_rate' => $requests->count() > 0 ?
                round(($requests->where('status', 'completed')->count() / $requests->count()) * 100, 2) : 0
        ];
    }

    /**
     * Get project progress report
     */
    public function getProjectProgressReport($projectId = null)
    {
        $query = Project::with(['client', 'pbcRequests.items']);

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        return $projects->map(function ($project) {
            $totalItems = $project->pbcRequests->sum(function ($request) {
                return $request->items->count();
            });

            $completedItems = $project->pbcRequests->sum(function ($request) {
                return $request->items->where('status', 'completed')->count();
            });

            return [
                'id' => $project->id,
                'client_name' => $project->client->name ?? 'Unknown',
                'engagement_type' => $project->engagement_type,
                'audit_period' => $project->audit_period,
                'total_requests' => $project->pbcRequests->count(),
                'completed_requests' => $project->pbcRequests->where('status', 'completed')->count(),
                'total_items' => $totalItems,
                'completed_items' => $completedItems,
                'progress_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0,
                'status' => $project->status,
                'created_at' => $project->created_at
            ];
        });
    }

    /**
     * Get audit trail report
     */
    public function getAuditTrailReport($filters = [])
    {
        // This would typically use an audit log table, but for now return basic activity
        $activities = collect();

        // Get recent PBC request activities
        $pbcRequests = PbcRequest::with(['project.client', 'assignedTo'])
            ->when(!empty($filters['date_from']), function ($q) use ($filters) {
                return $q->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function ($q) use ($filters) {
                return $q->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        foreach ($pbcRequests as $request) {
            $activities->push([
                'type' => 'pbc_request_created',
                'description' => "PBC Request '{$request->title}' was created",
                'user' => $request->assignedTo->name ?? 'System',
                'client' => $request->project->client->name ?? 'Unknown',
                'timestamp' => $request->created_at,
                'details' => [
                    'request_id' => $request->id,
                    'project_id' => $request->project_id,
                    'status' => $request->status
                ]
            ]);
        }

        return $activities->sortByDesc('timestamp')->values();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($userId = null)
    {
        $stats = [];

        // PBC Request stats
        $pbcQuery = PbcRequest::query();
        if ($userId) {
            $pbcQuery->where('assigned_to', $userId);
        }

        $stats['pbc_requests'] = [
            'total' => $pbcQuery->count(),
            'completed' => $pbcQuery->where('status', 'completed')->count(),
            'active' => $pbcQuery->where('status', 'active')->count(),
            'overdue' => $pbcQuery->where('status', '!=', 'completed')
                ->where('due_date', '<', now())
                ->count()
        ];

        // Project stats
        $stats['projects'] = [
            'total' => Project::count(),
            'active' => Project::where('status', 'active')->count(),
            'completed' => Project::where('status', 'completed')->count()
        ];

        // Recent activity
        $stats['recent_activity'] = $this->getRecentActivity($userId, 5);

        return $stats;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($userId = null, $limit = 10)
    {
        $query = PbcRequest::with(['project.client', 'assignedTo']);

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($request) {
                return [
                    'type' => 'pbc_request',
                    'title' => $request->title,
                    'client' => $request->project->client->name ?? 'Unknown',
                    'status' => $request->status,
                    'updated_at' => $request->updated_at
                ];
            });
    }

    /**
     * Export data to array format (for Excel/CSV)
     */
    public function exportPbcRequests($filters = [])
    {
        $data = $this->getPbcStatusReport($filters);

        return $data['requests']->map(function ($request) {
            return [
                'ID' => $request->id,
                'Title' => $request->title,
                'Client' => $request->project->client->name ?? 'Unknown',
                'Project' => $request->project->engagement_type ?? 'Unknown',
                'Status' => ucfirst($request->status),
                'Assigned To' => $request->assignedTo->name ?? 'Unassigned',
                'Due Date' => $request->due_date ? Carbon::parse($request->due_date)->format('Y-m-d') : 'No due date',
                'Created At' => $request->created_at->format('Y-m-d H:i:s'),
                'Completion %' => $request->completion_percentage ?? 0,
                'Notes' => $request->notes ?? ''
            ];
        })->toArray();
    }
}
