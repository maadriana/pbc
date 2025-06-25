<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePbcRequestRequest;
use App\Http\Requests\UpdatePbcRequestRequest;
use App\Models\PbcRequest;
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
            // Use custom permission check instead of $this->authorize()
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                abort(403, 'Unauthorized access');
            }

            $pbcRequests = $this->pbcRequestService->getFilteredPbcRequests($request->all(), $request->user());

            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return $this->paginated($pbcRequests, 'PBC requests retrieved successfully');
            }

            // For web requests, return the view
            return view('pbc-requests.index');

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
            // Permission check is already in CreatePbcRequestRequest
            $pbcRequest = $this->pbcRequestService->createPbcRequest($request->validated(), $request->user());
            return $this->success($pbcRequest, 'PBC request created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create PBC request', $e->getMessage(), 500);
        }
    }

    public function show(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $pbcRequest->load([
                'project.client',
                'category',
                'requestor',
                'assignedTo',
                'approvedBy',
                'documents.uploadedBy',
                'comments.user'
            ]);
            return $this->success($pbcRequest, 'PBC request retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve PBC request', $e->getMessage(), 500);
        }
    }

    public function update(UpdatePbcRequestRequest $request, PbcRequest $pbcRequest)
    {
        try {
            // Permission check is already in UpdatePbcRequestRequest
            $updatedPbcRequest = $this->pbcRequestService->updatePbcRequest($pbcRequest, $request->validated(), $request->user());
            return $this->success($updatedPbcRequest, 'PBC request updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update PBC request', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcRequest $pbcRequest)
    {
        try {
            if (!auth()->user()->hasPermission('delete_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->pbcRequestService->deletePbcRequest($pbcRequest);
            return $this->success(null, 'PBC request deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete PBC request', $e->getMessage(), 500);
        }
    }

    public function complete(PbcRequest $pbcRequest, Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->pbcRequestService->completePbcRequest($pbcRequest, $request->user());
            return $this->success(null, 'PBC request marked as completed');
        } catch (\Exception $e) {
            return $this->error('Failed to complete PBC request', $e->getMessage(), 500);
        }
    }

    public function reopen(PbcRequest $pbcRequest, Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->pbcRequestService->reopenPbcRequest($pbcRequest, $request->user());
            return $this->success(null, 'PBC request reopened');
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
                'pbc_request_ids' => 'required|array',
                'pbc_request_ids.*' => 'exists:pbc_requests,id',
                'action' => 'required|in:complete,reopen,delete,assign',
                'assigned_to_id' => 'required_if:action,assign|exists:users,id',
            ]);

            $result = $this->pbcRequestService->bulkUpdatePbcRequests(
                $request->pbc_request_ids,
                $request->action,
                $request->user(),
                $request->assigned_to_id
            );

            return $this->success($result, 'Bulk update completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk update', $e->getMessage(), 500);
        }
    }
}
