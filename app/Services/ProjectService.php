<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PbcRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function getFilteredProjects(array $filters): LengthAwarePaginator
    {
        $query = Project::with(['client', 'engagementPartner', 'manager', 'associate1', 'associate2'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($filters['engagement_type'] ?? null, function ($query, $type) {
                $query->where('engagement_type', $type);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['client_id'] ?? null, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($filters['engagement_partner_id'] ?? null, function ($query, $partnerId) {
                $query->where('engagement_partner_id', $partnerId);
            })
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createProject(array $projectData): Project
    {
        DB::beginTransaction();

        try {
            $project = Project::create($projectData);

            // Create team assignments
            $this->createTeamAssignments($project);

            DB::commit();
            return $project->load(['client', 'engagementPartner', 'manager', 'associate1', 'associate2']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateProject(Project $project, array $projectData): Project
    {
        DB::beginTransaction();

        try {
            // Store original team assignments for comparison
            $originalTeam = [
                'engagement_partner_id' => $project->engagement_partner_id,
                'manager_id' => $project->manager_id,
                'associate_1_id' => $project->associate_1_id,
                'associate_2_id' => $project->associate_2_id,
            ];

            // Update project data
            $project->update($projectData);

            // Check if team assignments changed
            $newTeam = [
                'engagement_partner_id' => $project->engagement_partner_id,
                'manager_id' => $project->manager_id,
                'associate_1_id' => $project->associate_1_id,
                'associate_2_id' => $project->associate_2_id,
            ];

            if ($originalTeam !== $newTeam) {
                $this->updateTeamAssignments($project);
            }

            DB::commit();
            return $project->fresh(['client', 'engagementPartner', 'manager', 'associate1', 'associate2']);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('ProjectService updateProject failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'projectData' => $projectData,
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteProject(Project $project): bool
    {
        DB::beginTransaction();

        try {
            // Deactivate team assignments first
            $project->teamAssignments()->update(['is_active' => false, 'end_date' => now()]);

            // Soft delete the project
            $result = $project->delete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getProjectPbcRequests(Project $project): array
    {
        return $project->pbcRequests()
            ->with(['category', 'requestor', 'assignedTo', 'documents'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->toArray();
    }

    public function getProjectStatistics(Project $project): array
    {
        $totalRequests = $project->pbcRequests()->count();
        $completedRequests = $project->pbcRequests()->where('status', 'completed')->count();
        $pendingRequests = $project->pbcRequests()->where('status', 'pending')->count();
        $overdueRequests = $project->pbcRequests()->where('status', 'overdue')->count();

        return [
            'total_requests' => $totalRequests,
            'completed_requests' => $completedRequests,
            'pending_requests' => $pendingRequests,
            'overdue_requests' => $overdueRequests,
            'progress_percentage' => $project->progress_percentage,
            'completion_rate' => $totalRequests > 0
                ? round(($completedRequests / $totalRequests) * 100, 2)
                : 0
        ];
    }

    private function createTeamAssignments(Project $project): void
    {
        $assignments = [];

        if ($project->engagement_partner_id) {
            $assignments[] = [
                'project_id' => $project->id,
                'user_id' => $project->engagement_partner_id,
                'role' => 'engagement_partner',
                'assigned_date' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($project->manager_id) {
            $assignments[] = [
                'project_id' => $project->id,
                'user_id' => $project->manager_id,
                'role' => 'manager',
                'assigned_date' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($project->associate_1_id) {
            $assignments[] = [
                'project_id' => $project->id,
                'user_id' => $project->associate_1_id,
                'role' => 'associate',
                'assigned_date' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($project->associate_2_id) {
            $assignments[] = [
                'project_id' => $project->id,
                'user_id' => $project->associate_2_id,
                'role' => 'associate',
                'assigned_date' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($assignments)) {
            \App\Models\ProjectTeamAssignment::insert($assignments);
        }
    }

    private function updateTeamAssignments(Project $project): void
    {
        // FIXED: Delete existing assignments completely to avoid duplicates
        \App\Models\ProjectTeamAssignment::where('project_id', $project->id)->delete();

        // Create new assignments
        $this->createTeamAssignments($project);
    }
}
