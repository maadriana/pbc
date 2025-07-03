<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePbcRequestRequest;
use App\Http\Requests\UpdatePbcRequestRequest;
use App\Models\PbcRequest;
use App\Models\Project;
use App\Models\PbcTemplate;
use App\Services\PbcRequestService;
use Illuminate\Http\Request;

class PbcRequestController extends BaseController
{
    protected $pbcRequestService;

    public function __construct(PbcRequestService $pbcRequestService)
    {
        $this->pbcRequestService = $pbcRequestService;
    }

    public function index(Request $request)
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                abort(403, 'Unauthorized access');
            }

            $pbcRequests = $this->pbcRequestService->getFilteredPbcRequests($request->all());

            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return $this->paginated($pbcRequests, 'PBC requests retrieved successfully');
            }

            // For web requests, return the view
            return view('pbc-requests.index', compact('pbcRequests'));

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve PBC requests: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to retrieve PBC requests', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to retrieve PBC requests: ' . $e->getMessage()]);
        }
    }

    public function store(CreatePbcRequestRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_pbc_request')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $pbcRequest = $this->pbcRequestService->createPbcRequest($request->validated());

            if ($request->expectsJson()) {
                return $this->success($pbcRequest, 'PBC request created successfully', 201);
            }

            return redirect()->route('pbc-requests.index')->with('success', 'PBC request created successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to create PBC request: ' . $e->getMessage(), [
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to create PBC request', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to create PBC request: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            // Check if user can view this specific request
            if (!$pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC request', null, 403);
            }

            $pbcRequest->load([
                'project.client',
                'template',
                'creator',
                'assignedTo',
                'items.category',
                'items.submissions' => function($query) {
                    $query->where('is_active', true)->latest('version');
                },
                'comments' => function($query) {
                    $query->where('visibility', 'both')
                          ->orWhere('visibility', auth()->user()->isGuest() ? 'client' : 'internal')
                          ->whereNull('parent_id')
                          ->with('replies', 'user')
                          ->latest();
                }
            ]);

            return $this->success($pbcRequest, 'PBC request retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve PBC request', $e->getMessage(), 500);
        }
    }

    public function update(UpdatePbcRequestRequest $request, PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            // Check if user can edit this specific request
            if (!$pbcRequest->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to edit this PBC request', null, 403);
            }

            $updatedPbcRequest = $this->pbcRequestService->updatePbcRequest($pbcRequest, $request->validated());

            if ($request->expectsJson()) {
                return $this->success($updatedPbcRequest, 'PBC request updated successfully');
            }

            return redirect()->route('pbc-requests.index')->with('success', 'PBC request updated successfully');

        } catch (\Exception $e) {
            \Log::error('PBC request update failed: ' . $e->getMessage(), [
                'pbc_request_id' => $pbcRequest->id,
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to update PBC request', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to update PBC request: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('delete_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            // Check if user can delete this specific request
            if (!$pbcRequest->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to delete this PBC request', null, 403);
            }

            $this->pbcRequestService->deletePbcRequest($pbcRequest);
            return $this->success(null, 'PBC request deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete PBC request', $e->getMessage(), 500);
        }
    }

    public function complete(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequest->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to modify this PBC request', null, 403);
            }

            $completedRequest = $this->pbcRequestService->markAsCompleted($pbcRequest);
            return $this->success($completedRequest, 'PBC request marked as completed');
        } catch (\Exception $e) {
            return $this->error('Failed to complete PBC request', $e->getMessage(), 500);
        }
    }

    public function reopen(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequest->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to modify this PBC request', null, 403);
            }

            $reopenedRequest = $this->pbcRequestService->reopenRequest($pbcRequest);
            return $this->success($reopenedRequest, 'PBC request reopened successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to reopen PBC request', $e->getMessage(), 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'request_ids' => 'required|array',
                'request_ids.*' => 'exists:pbc_requests,id',
                'action' => 'required|in:complete,reopen,delete,assign,update_due_date',
                'data' => 'sometimes|array',
            ]);

            $result = $this->pbcRequestService->bulkUpdateRequests(
                $request->request_ids,
                $request->action,
                $request->data ?? []
            );

            return $this->success($result, 'Bulk update completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk update', $e->getMessage(), 500);
        }
    }

    public function createFromTemplate(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'template_id' => 'required|exists:pbc_templates,id',
                'assigned_to' => 'nullable|exists:users,id',
                'due_date' => 'nullable|date|after:today',
                'title' => 'nullable|string|max:255',
            ]);

            $project = Project::findOrFail($request->project_id);
            $template = PbcTemplate::findOrFail($request->template_id);

            // Check if template can be used for this engagement type
            if (!$template->canBeUsedForEngagement($project->engagement_type)) {
                return $this->error('Selected template cannot be used for this engagement type', null, 422);
            }

            $pbcRequest = $project->createPbcRequestFromTemplate(
                $request->template_id,
                auth()->id(),
                $request->assigned_to
            );

            // Update title and due date if provided
            $updates = [];
            if ($request->title) {
                $updates['title'] = $request->title;
            }
            if ($request->due_date) {
                $updates['due_date'] = $request->due_date;
            }
            if (!empty($updates)) {
                $pbcRequest->update($updates);
            }

            return $this->success($pbcRequest->load(['project', 'template', 'items']),
                'PBC request created from template successfully', 201);

        } catch (\Exception $e) {
            return $this->error('Failed to create PBC request from template', $e->getMessage(), 500);
        }
    }

    public function getProgress(PbcRequest $pbcRequest)
    {
        try {
            if (!$pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC request', null, 403);
            }

            $progress = $this->pbcRequestService->getDetailedProgress($pbcRequest);
            return $this->success($progress, 'Progress data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve progress data', $e->getMessage(), 500);
        }
    }

    public function getAvailableTemplates(Request $request)
    {
        try {
            $request->validate([
                'engagement_type' => 'sometimes|string',
                'project_id' => 'sometimes|exists:projects,id',
            ]);

            $templates = $this->pbcRequestService->getAvailableTemplates(
                $request->engagement_type,
                $request->project_id
            );

            return $this->success($templates, 'Available templates retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve templates', $e->getMessage(), 500);
        }
    }

    public function duplicate(PbcRequest $pbcRequest, Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC request', null, 403);
            }

            $request->validate([
                'title' => 'nullable|string|max:255',
                'assigned_to' => 'nullable|exists:users,id',
                'due_date' => 'nullable|date|after:today',
            ]);

            $duplicatedRequest = $this->pbcRequestService->duplicateRequest(
                $pbcRequest,
                $request->only(['title', 'assigned_to', 'due_date'])
            );

            return $this->success($duplicatedRequest, 'PBC request duplicated successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to duplicate PBC request', $e->getMessage(), 500);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'format' => 'sometimes|in:excel,pdf,csv',
                'request_ids' => 'sometimes|array',
                'request_ids.*' => 'exists:pbc_requests,id',
            ]);

            $format = $request->format ?? 'excel';
            $requestIds = $request->request_ids;

            $exportData = $this->pbcRequestService->exportRequests($format, $requestIds);

            return response()->download($exportData['file_path'], $exportData['filename']);
        } catch (\Exception $e) {
            return $this->error('Failed to export PBC requests', $e->getMessage(), 500);
        }
    }
}
