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
            // Check permission
            if (!auth()->user()->hasPermission('view_project')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                abort(403, 'Unauthorized access');
            }

            $projects = $this->projectService->getFilteredProjects($request->all());

            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return $this->paginated($projects, 'Projects retrieved successfully');
            }

            // For web requests, return the view
            return view('projects.index', compact('projects'));

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve projects: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to retrieve projects', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to retrieve projects: ' . $e->getMessage()]);
        }
    }

    public function store(CreateProjectRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_project')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $project = $this->projectService->createProject($request->validated());

            if ($request->expectsJson()) {
                return $this->success($project, 'Project created successfully', 201);
            }

            return redirect()->route('projects.index')->with('success', 'Project created successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to create project: ' . $e->getMessage(), [
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to create project', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to create project: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Project $project)
    {
        try {
            if (!auth()->user()->hasPermission('view_project')) {
                return $this->error('Unauthorized access', null, 403);
            }

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
            if (!auth()->user()->hasPermission('edit_project')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $updatedProject = $this->projectService->updateProject($project, $request->validated());

            if ($request->expectsJson()) {
                return $this->success($updatedProject, 'Project updated successfully');
            }

            return redirect()->route('projects.index')->with('success', 'Project updated successfully');

        } catch (\Exception $e) {
            \Log::error('Project update failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to update project', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to update project: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Project $project)
    {
        try {
            if (!auth()->user()->hasPermission('delete_project')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->projectService->deleteProject($project);
            return $this->success(null, 'Project deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete project', $e->getMessage(), 500);
        }
    }

    public function pbcRequests(Project $project)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $pbcRequests = $this->projectService->getProjectPbcRequests($project);
            return $this->success($pbcRequests, 'Project PBC requests retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve project PBC requests', $e->getMessage(), 500);
        }
    }

    public function updateProgress(Project $project)
    {
        try {
            if (!auth()->user()->hasPermission('edit_project')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $project->updateProgress();
            return $this->success([
                'progress_percentage' => $project->fresh()->progress_percentage
            ], 'Project progress updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update project progress', $e->getMessage(), 500);
        }
    }

    /**
     * Export projects to Excel
     */
    public function export(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_project')) {
                return $this->error('Unauthorized access', null, 403);
            }

            // Implementation for export functionality
            // You can implement this later with Excel export package
            return $this->success(null, 'Export functionality coming soon');
        } catch (\Exception $e) {
            return $this->error('Failed to export projects', $e->getMessage(), 500);
        }
    }
}
