<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcRequest;
use App\Models\PbcDocument;
use App\Models\AuditLog;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData(User $user): array
    {
        return [
            'statistics' => $this->getStatistics($user),
            'recent_activity' => $this->getRecentActivity($user),
            'pending_requests' => $this->getPendingRequests($user),
            'overdue_requests' => $this->getOverdueRequests($user),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($user),
            'charts_data' => $this->getChartsData($user),
        ];
    }

    public function getStatistics(User $user): array
{
    try {
        $stats = [];

        if ($user->isSystemAdmin() || $user->isEngagementPartner()) {
            $stats = [
                'total_clients' => Client::count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_users' => User::count(),
                'total_requests' => PbcRequest::count(),
                'completed_requests' => PbcRequest::where('status', 'completed')->count(),
                'pending_requests' => PbcRequest::where('status', 'pending')->count(),
                'overdue_requests' => PbcRequest::overdue()->count(),
                'documents_uploaded' => PbcDocument::count(),
                'documents_approved' => PbcDocument::where('status', 'approved')->count(),
            ];
        } elseif ($user->isManager() || $user->isAssociate()) {
            $projectIds = $this->getUserProjectIds($user);

            $stats = [
                'my_projects' => count($projectIds),
                'my_requests' => PbcRequest::where('requestor_id', $user->id)->count(),
                'assigned_requests' => PbcRequest::where('assigned_to_id', $user->id)->count(),
                'total_requests' => PbcRequest::whereIn('project_id', $projectIds)->count(),
                'completed_requests' => PbcRequest::where('requestor_id', $user->id)
                    ->where('status', 'completed')->count(),
                'pending_requests' => PbcRequest::where('assigned_to_id', $user->id)
                    ->where('status', 'pending')->count(),
                'overdue_requests' => PbcRequest::where('assigned_to_id', $user->id)
                    ->overdue()->count(),
            ];
        } else { // Guest/Client
            $stats = [
                'assigned_requests' => PbcRequest::where('assigned_to_id', $user->id)->count(),
                'total_requests' => PbcRequest::where('assigned_to_id', $user->id)->count(),
                'completed_requests' => PbcRequest::where('assigned_to_id', $user->id)
                    ->where('status', 'completed')->count(),
                'pending_requests' => PbcRequest::where('assigned_to_id', $user->id)
                    ->where('status', 'pending')->count(),
                'overdue_requests' => PbcRequest::where('assigned_to_id', $user->id)
                    ->overdue()->count(),
                'documents_uploaded' => PbcDocument::where('uploaded_by', $user->id)->count(),
            ];
        }

        // Calculate completion rate
        $totalRequests = $stats['total_requests'] ?? 0;
        $completedRequests = $stats['completed_requests'] ?? 0;
        $stats['completion_rate'] = $totalRequests > 0
            ? round(($completedRequests / $totalRequests) * 100, 2)
            : 0;

        return $stats;
    } catch (\Exception $e) {
        \Log::error('Dashboard statistics error: ' . $e->getMessage());

        // Return default stats if there's an error
        return [
            'total_clients' => 0,
            'active_projects' => 0,
            'active_users' => 0,
            'total_users' => User::count(),
            'total_requests' => 0,
            'completed_requests' => 0,
            'pending_requests' => 0,
            'overdue_requests' => 0,
            'documents_uploaded' => 0,
            'documents_approved' => 0,
            'completion_rate' => 0,
        ];
    }
}

    public function getRecentActivity(User $user): array
    {
        $query = AuditLog::with(['user'])
            ->when(!$user->isSystemAdmin(), function ($q) use ($user) {
                return $q->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10);

        return $query->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'Unknown',
                'created_at' => $log->created_at,
                'formatted_date' => $log->created_at->diffForHumans(),
            ];
        })->toArray();
    }

    public function getPendingRequests(User $user): array
    {
        $query = PbcRequest::with(['project.client', 'category', 'assignedTo'])
            ->where('status', 'pending');

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        return $query->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getOverdueRequests(User $user): array
    {
        $query = PbcRequest::with(['project.client', 'category', 'assignedTo'])
            ->overdue();

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        return $query->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getUpcomingDeadlines(User $user): array
    {
        $query = PbcRequest::with(['project.client', 'category', 'assignedTo'])
            ->where('status', 'pending')
            ->where('due_date', '<=', Carbon::now()->addDays(7))
            ->where('due_date', '>=', Carbon::now());

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        return $query->orderBy('due_date', 'asc')
            ->get()
            ->toArray();
    }

    public function getChartsData(User $user): array
    {
        return [
            'requests_by_status' => $this->getRequestsByStatus($user),
            'requests_by_category' => $this->getRequestsByCategory($user),
            'requests_by_priority' => $this->getRequestsByPriority($user),
            'completion_trend' => $this->getCompletionTrend($user),
        ];
    }

    private function getUserProjectIds(User $user): array
    {
        return Project::where(function ($query) use ($user) {
            $query->where('engagement_partner_id', $user->id)
                  ->orWhere('manager_id', $user->id)
                  ->orWhere('associate_1_id', $user->id)
                  ->orWhere('associate_2_id', $user->id);
        })->pluck('id')->toArray();
    }

    private function getRequestsByStatus(User $user): array
    {
        $query = PbcRequest::selectRaw('status, COUNT(*) as count')
            ->groupBy('status');

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        return $query->get()->map(function ($item) {
            return [
                'label' => ucfirst(str_replace('_', ' ', $item->status)),
                'value' => $item->count,
                'status' => $item->status,
            ];
        })->toArray();
    }

    private function getRequestsByCategory(User $user): array
    {
        $query = PbcRequest::join('pbc_categories', 'pbc_requests.category_id', '=', 'pbc_categories.id')
            ->selectRaw('pbc_categories.name, pbc_categories.code, COUNT(*) as count')
            ->groupBy('pbc_categories.id', 'pbc_categories.name', 'pbc_categories.code');

        if ($user->isGuest()) {
            $query->where('pbc_requests.assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('pbc_requests.project_id', $projectIds);
        }

        return $query->get()->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->count,
                'code' => $item->code,
            ];
        })->toArray();
    }

    private function getRequestsByPriority(User $user): array
    {
        $query = PbcRequest::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority');

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        return $query->get()->map(function ($item) {
            return [
                'label' => ucfirst($item->priority),
                'value' => $item->count,
                'priority' => $item->priority,
            ];
        })->toArray();
    }

    private function getCompletionTrend(User $user): array
    {
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last30Days->push([
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
                'completed' => 0,
                'total' => 0,
            ]);
        }

        $query = PbcRequest::selectRaw('DATE(completed_at) as date, COUNT(*) as completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date');

        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        $completedData = $query->get()->keyBy('date');

        return $last30Days->map(function ($day) use ($completedData) {
            $completed = $completedData->get($day['date'])?->completed ?? 0;
            return [
                'date' => $day['date'],
                'label' => $day['label'],
                'completed' => $completed,
            ];
        })->toArray();
    }
}
