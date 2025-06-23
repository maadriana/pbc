<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends BaseController
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('view_project');

            $projects = $this->projectService->getFilteredProjects($request->all());
            return $this->paginated($projects, 'Projects retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve projects', $e->getMessage(), 500);
        }
    }

    public function store(CreateProjectRequest $request)
    {
        try {
            $this->authorize('create_project');

            $project = $this->projectService->createProject($request->validated());
            return $this->success($project, 'Project created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create project', $e->getMessage(), 500);
        }
    }

    public function show(Project $project)
    {
        try {
            $this->authorize('view_project');

            $project->load([
                'client',
                'engagementPartner',
                'manager',
                'associate1',
                'associate2',
                'pbcRequests.category',
                'pbcRequests.assignedTo',
                'pbcRequests.requestor'
            ]);
            return $this->success($project, 'Project retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve project', $e->getMessage(), 500);
        }
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        try {
            $this->authorize('edit_project');

            $updatedProject = $this->projectService->updateProject($project, $request->validated());
            return $this->success($updatedProject, 'Project updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update project', $e->getMessage(), 500);
        }
    }

    public function destroy(Project $project)
    {
        try {
            $this->authorize('delete_project');

            $this->projectService->deleteProject($project);
            return $this->success(null, 'Project deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete project', $e->getMessage(), 500);
        }
    }

    public function pbcRequests(Project $project)
    {
        try {
            $this->authorize('view_pbc_request');

            $pbcRequests = $this->projectService->getProjectPbcRequests($project);
            return $this->success($pbcRequests, 'Project PBC requests retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve project PBC requests', $e->getMessage(), 500);
        }
    }

    public function updateProgress(Project $project)
    {
        try {
            $this->authorize('edit_project');

            $project->updateProgress();
            return $this->success([
                'progress_percentage' => $project->fresh()->progress_percentage
            ], 'Project progress updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update project progress', $e->getMessage(), 500);
        }
    }
}
