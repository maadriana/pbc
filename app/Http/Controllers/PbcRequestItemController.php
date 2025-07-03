<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePbcRequestItemRequest;
use App\Http\Requests\UpdatePbcRequestItemRequest;
use App\Models\PbcRequestItem;
use App\Models\PbcRequest;
use App\Services\PbcRequestItemService;
use Illuminate\Http\Request;

class PbcRequestItemController extends BaseController
{
    protected $pbcRequestItemService;

    public function __construct(PbcRequestItemService $pbcRequestItemService)
    {
        $this->pbcRequestItemService = $pbcRequestItemService;
    }

    public function index(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $items = $this->pbcRequestItemService->getFilteredItems($request->all());
            return $this->paginated($items, 'PBC request items retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve PBC request items', $e->getMessage(), 500);
        }
    }

    public function store(CreatePbcRequestItemRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $item = $this->pbcRequestItemService->createCustomItem($request->validated());
            return $this->success($item, 'Custom PBC item created successfully', 201);

        } catch (\Exception $e) {
            return $this->error('Failed to create PBC item', $e->getMessage(), 500);
        }
    }

    public function show(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!$pbcRequestItem->pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC item', null, 403);
            }

            $pbcRequestItem->load([
                'pbcRequest',
                'category',
                'parent',
                'children',
                'submissions' => function($query) {
                    $query->where('is_active', true)->latest('version');
                },
                'comments' => function($query) {
                    $query->where('visibility', 'both')
                          ->orWhere('visibility', auth()->user()->isGuest() ? 'client' : 'internal')
                          ->with('user')
                          ->latest();
                }
            ]);

            return $this->success($pbcRequestItem, 'PBC item retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve PBC item', $e->getMessage(), 500);
        }
    }

    public function update(UpdatePbcRequestItemRequest $request, PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequestItem->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to edit this PBC item', null, 403);
            }

            $updatedItem = $this->pbcRequestItemService->updateItem($pbcRequestItem, $request->validated());
            return $this->success($updatedItem, 'PBC item updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update PBC item', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequestItem->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to delete this PBC item', null, 403);
            }

            // Only allow deletion of custom items
            if (!$pbcRequestItem->is_custom) {
                return $this->error('Cannot delete template-based items', null, 422);
            }

            $this->pbcRequestItemService->deleteItem($pbcRequestItem);
            return $this->success(null, 'PBC item deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete PBC item', $e->getMessage(), 500);
        }
    }

    public function accept(PbcRequestItem $pbcRequestItem, Request $request)
    {
        try {
            if (!$pbcRequestItem->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to review this PBC item', null, 403);
            }

            $request->validate([
                'remarks' => 'nullable|string|max:1000',
            ]);

            $acceptedItem = $this->pbcRequestItemService->acceptItem(
                $pbcRequestItem,
                auth()->id(),
                $request->remarks
            );

            return $this->success($acceptedItem, 'PBC item accepted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to accept PBC item', $e->getMessage(), 500);
        }
    }

    public function reject(PbcRequestItem $pbcRequestItem, Request $request)
    {
        try {
            if (!$pbcRequestItem->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to review this PBC item', null, 403);
            }

            $request->validate([
                'remarks' => 'required|string|max:1000',
            ]);

            $rejectedItem = $this->pbcRequestItemService->rejectItem(
                $pbcRequestItem,
                auth()->id(),
                $request->remarks
            );

            return $this->success($rejectedItem, 'PBC item rejected');
        } catch (\Exception $e) {
            return $this->error('Failed to reject PBC item', $e->getMessage(), 500);
        }
    }

    public function submit(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!$pbcRequestItem->canUploadFilesBy(auth()->user())) {
                return $this->error('Access denied to submit this PBC item', null, 403);
            }

            // Check if item has active submissions
            if (!$pbcRequestItem->hasActiveSubmissions()) {
                return $this->error('Cannot submit item without any documents', null, 422);
            }

            $submittedItem = $this->pbcRequestItemService->submitForReview(
                $pbcRequestItem,
                auth()->id()
            );

            return $this->success($submittedItem, 'PBC item submitted for review');
        } catch (\Exception $e) {
            return $this->error('Failed to submit PBC item', $e->getMessage(), 500);
        }
    }

    public function resetToPending(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!$pbcRequestItem->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to modify this PBC item', null, 403);
            }

            $resetItem = $this->pbcRequestItemService->resetToPending(
                $pbcRequestItem,
                auth()->id()
            );

            return $this->success($resetItem, 'PBC item reset to pending status');
        } catch (\Exception $e) {
            return $this->error('Failed to reset PBC item', $e->getMessage(), 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'item_ids' => 'required|array',
                'item_ids.*' => 'exists:pbc_request_items,id',
                'action' => 'required|in:accept,reject,submit,reset,assign,update_due_date',
                'data' => 'sometimes|array',
            ]);

            $result = $this->pbcRequestItemService->bulkUpdateItems(
                $request->item_ids,
                $request->action,
                $request->data ?? []
            );

            return $this->success($result, 'Bulk update completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk update', $e->getMessage(), 500);
        }
    }

    public function getByRequest(PbcRequest $pbcRequest, Request $request)
    {
        try {
            if (!$pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC request', null, 403);
            }

            $filters = array_merge($request->all(), ['pbc_request_id' => $pbcRequest->id]);
            $items = $this->pbcRequestItemService->getFilteredItems($filters);

            return $this->success($items, 'PBC request items retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve PBC request items', $e->getMessage(), 500);
        }
    }

    public function getGroupedByCategory(PbcRequest $pbcRequest)
    {
        try {
            if (!$pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC request', null, 403);
            }

            $groupedItems = $this->pbcRequestItemService->getItemsGroupedByCategory($pbcRequest);
            return $this->success($groupedItems, 'Grouped items retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve grouped items', $e->getMessage(), 500);
        }
    }

    public function duplicate(PbcRequestItem $pbcRequestItem, Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('edit_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            if (!$pbcRequestItem->pbcRequest->canBeEditedBy(auth()->user())) {
                return $this->error('Access denied to modify this PBC request', null, 403);
            }

            $request->validate([
                'description' => 'nullable|string|max:1000',
                'assigned_to' => 'nullable|exists:users,id',
                'due_date' => 'nullable|date|after:today',
            ]);

            $duplicatedItem = $this->pbcRequestItemService->duplicateItem(
                $pbcRequestItem,
                $request->only(['description', 'assigned_to', 'due_date'])
            );

            return $this->success($duplicatedItem, 'PBC item duplicated successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to duplicate PBC item', $e->getMessage(), 500);
        }
    }

    public function updateDaysOutstanding(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!$pbcRequestItem->pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC item', null, 403);
            }

            $updatedItem = $pbcRequestItem->updateDaysOutstanding();
            return $this->success($updatedItem, 'Days outstanding updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update days outstanding', $e->getMessage(), 500);
        }
    }

    public function getOverdueItems(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_pbc_request')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $overdueItems = $this->pbcRequestItemService->getOverdueItems($request->all());
            return $this->paginated($overdueItems, 'Overdue items retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve overdue items', $e->getMessage(), 500);
        }
    }
}
