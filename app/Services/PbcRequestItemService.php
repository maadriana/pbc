<?php

namespace App\Services;

use App\Models\PbcRequestItem;
use App\Models\PbcRequest;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PbcRequestItemService
{
    public function getFilteredItems(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = PbcRequestItem::with([
            'pbcRequest.project.client',
            'category',
            'parent',
            'children',
            'requestor',
            'assignedTo',
            'reviewer'
        ]);

        // Apply access control
        if ($user->isGuest()) {
            $query->whereHas('pbcRequest', function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('project', function($projectQuery) use ($user) {
                      $projectQuery->where('contact_email', $user->email);
                  });
            });
        } elseif (!$user->isSystemAdmin()) {
            $query->whereHas('pbcRequest.project', function($projectQuery) use ($user) {
                $projectQuery->where(function($q) use ($user) {
                    $q->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
                });
            });
        }

        // Apply filters
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhere('client_remarks', 'like', "%{$search}%");
            });
        })
        ->when($filters['pbc_request_id'] ?? null, function ($query, $requestId) {
            $query->where('pbc_request_id', $requestId);
        })
        ->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->when($filters['assigned_to'] ?? null, function ($query, $assignedTo) {
            $query->where('assigned_to', $assignedTo);
        })
        ->when($filters['is_required'] ?? null, function ($query, $isRequired) {
            $query->where('is_required', $isRequired);
        })
        ->when($filters['is_custom'] ?? null, function ($query, $isCustom) {
            $query->where('is_custom', $isCustom);
        })
        ->when($filters['overdue'] ?? null, function ($query) {
            $query->where('due_date', '<', now())
                  ->whereNotIn('status', ['accepted']);
        })
        ->when($filters['parent_id'] ?? null, function ($query, $parentId) {
            if ($parentId === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        });

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createCustomItem(array $itemData): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            // Set defaults for custom items
            $itemData = array_merge([
                'status' => 'pending',
                'date_requested' => now(),
                'requested_by' => auth()->id(),
                'is_custom' => true,
                'is_required' => $itemData['is_required'] ?? false,
            ], $itemData);

            // Create the item
            $item = PbcRequestItem::create($itemData);

            // Update parent request progress
            $item->pbcRequest->updateProgress();

            // Log activity
            AuditLog::logPbcActivity('created', $item,
                "Custom PBC item '{$item->getDisplayName()}' created", auth()->user());

            DB::commit();
            return $item->load(['category', 'pbcRequest', 'parent']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateItem(PbcRequestItem $item, array $itemData): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            // Store old values for audit log
            $oldValues = $item->toArray();

            // Update the item
            $item->update($itemData);

            // Update days outstanding if status changed
            if (isset($itemData['status'])) {
                $item->updateDaysOutstanding();
            }

            // Update parent request progress
            $item->pbcRequest->updateProgress();

            // Log activity
            AuditLog::logModelChange('updated', $item, $oldValues, $item->fresh()->toArray());

            DB::commit();
            return $item->fresh(['category', 'pbcRequest', 'parent']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deleteItem(PbcRequestItem $item): bool
    {
        DB::beginTransaction();

        try {
            // Only allow deletion of custom items
            if (!$item->is_custom) {
                throw new \Exception('Cannot delete template-based items');
            }

            // Log activity before deletion
            AuditLog::logPbcActivity('deleted', $item,
                "Custom PBC item '{$item->getDisplayName()}' deleted", auth()->user());

            // Delete the item
            $result = $item->delete();

            // Update parent request progress
            $item->pbcRequest->updateProgress();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function acceptItem(PbcRequestItem $item, int $reviewedBy, ?string $remarks = null): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $item->accept($reviewedBy, $remarks);

            DB::commit();
            return $item->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function rejectItem(PbcRequestItem $item, int $reviewedBy, string $remarks): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $item->reject($reviewedBy, $remarks);

            DB::commit();
            return $item->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function submitForReview(PbcRequestItem $item, int $submittedBy): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $item->submitForReview($submittedBy);

            DB::commit();
            return $item->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function resetToPending(PbcRequestItem $item, int $userId): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $item->resetToPending($userId);

            DB::commit();
            return $item->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkUpdateItems(array $itemIds, string $action, array $data = []): array
    {
        DB::beginTransaction();

        try {
            $items = PbcRequestItem::whereIn('id', $itemIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($items as $item) {
                try {
                    // Check permissions for each item
                    $canPerformAction = match($action) {
                        'accept', 'reject' => $item->canBeReviewedBy(auth()->user()),
                        'submit' => $item->canUploadFilesBy(auth()->user()),
                        'reset', 'assign', 'update_due_date' => $item->canBeEditedBy(auth()->user()),
                        default => false
                    };

                    if (!$canPerformAction) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for item: {$item->getDisplayName()}";
                        continue;
                    }

                    switch ($action) {
                        case 'accept':
                            $this->acceptItem($item, auth()->id(), $data['remarks'] ?? null);
                            break;
                        case 'reject':
                            if (empty($data['remarks'])) {
                                throw new \Exception('Remarks required for rejection');
                            }
                            $this->rejectItem($item, auth()->id(), $data['remarks']);
                            break;
                        case 'submit':
                            $this->submitForReview($item, auth()->id());
                            break;
                        case 'reset':
                            $this->resetToPending($item, auth()->id());
                            break;
                        case 'assign':
                            if (isset($data['assigned_to'])) {
                                $item->update(['assigned_to' => $data['assigned_to']]);
                            }
                            break;
                        case 'update_due_date':
                            if (isset($data['due_date'])) {
                                $item->update(['due_date' => $data['due_date']]);
                            }
                            break;
                        default:
                            throw new \Exception("Unknown action: {$action}");
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to {$action} item '{$item->getDisplayName()}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getItemsGroupedByCategory(PbcRequest $pbcRequest): array
    {
        $items = $pbcRequest->items()
            ->with(['category', 'parent', 'children', 'submissions' => function($query) {
                $query->where('is_active', true)->latest('version');
            }])
            ->orderBy('sort_order')
            ->get();

        $grouped = [];

        foreach ($items->groupBy('category.name') as $categoryName => $categoryItems) {
            $grouped[$categoryName] = [
                'category_info' => $categoryItems->first()->category,
                'main_items' => [],
                'stats' => [
                    'total' => $categoryItems->count(),
                    'completed' => $categoryItems->where('status', 'accepted')->count(),
                    'pending' => $categoryItems->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
                    'rejected' => $categoryItems->where('status', 'rejected')->count(),
                    'completion_rate' => $categoryItems->count() > 0
                        ? ($categoryItems->where('status', 'accepted')->count() / $categoryItems->count()) * 100
                        : 0,
                ]
            ];

            // Group main items and their children
            $mainItems = $categoryItems->whereNull('parent_id');

            foreach ($mainItems as $mainItem) {
                $itemData = $mainItem->toArray();
                $itemData['children'] = $categoryItems->where('parent_id', $mainItem->id)->values()->toArray();
                $itemData['submissions_count'] = $mainItem->submissions->count();
                $itemData['latest_submission'] = $mainItem->getLatestSubmission();

                $grouped[$categoryName]['main_items'][] = $itemData;
            }
        }

        return $grouped;
    }

    public function duplicateItem(PbcRequestItem $item, array $overrides = []): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $newItem = $item->duplicate($item->pbc_request_id, auth()->id());

            // Apply overrides
            if (!empty($overrides)) {
                $newItem->update($overrides);
            }

            // Log activity
            AuditLog::logPbcActivity('duplicated', $newItem,
                "PBC item duplicated from '{$item->getDisplayName()}'", auth()->user());

            DB::commit();
            return $newItem->load(['category', 'pbcRequest']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getOverdueItems(array $filters = []): LengthAwarePaginator
    {
        $filters = array_merge($filters, ['overdue' => true]);
        return $this->getFilteredItems($filters);
    }

    public function getPendingItemsForUser(int $userId): LengthAwarePaginator
    {
        return $this->getFilteredItems([
            'assigned_to' => $userId,
            'status' => 'pending',
            'per_page' => 10
        ]);
    }

    public function getItemsNearDueDate(int $days = 3): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = PbcRequestItem::with([
            'pbcRequest.project.client',
            'category',
            'assignedTo'
        ])
        ->where('due_date', '>=', now())
        ->where('due_date', '<=', now()->addDays($days))
        ->whereNotIn('status', ['accepted', 'cancelled']);

        // Apply access control
        if ($user->isGuest()) {
            $query->where('assigned_to', $user->id);
        } elseif (!$user->isSystemAdmin()) {
            $query->whereHas('pbcRequest.project', function($projectQuery) use ($user) {
                $projectQuery->where(function($q) use ($user) {
                    $q->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
                });
            });
        }

        return $query->orderBy('due_date')->paginate(15);
    }

    public function getItemStatistics(PbcRequest $pbcRequest): array
    {
        $items = $pbcRequest->items;

        return [
            'total_items' => $items->count(),
            'required_items' => $items->where('is_required', true)->count(),
            'custom_items' => $items->where('is_custom', true)->count(),
            'by_status' => [
                'pending' => $items->where('status', 'pending')->count(),
                'submitted' => $items->where('status', 'submitted')->count(),
                'under_review' => $items->where('status', 'under_review')->count(),
                'accepted' => $items->where('status', 'accepted')->count(),
                'rejected' => $items->where('status', 'rejected')->count(),
                'overdue' => $items->where('status', 'overdue')->count(),
            ],
            'by_category' => $items->groupBy('category.name')->map(function($categoryItems) {
                return [
                    'total' => $categoryItems->count(),
                    'completed' => $categoryItems->where('status', 'accepted')->count(),
                    'completion_rate' => $categoryItems->count() > 0
                        ? ($categoryItems->where('status', 'accepted')->count() / $categoryItems->count()) * 100
                        : 0,
                ];
            })->toArray(),
            'average_days_outstanding' => $items->where('status', '!=', 'accepted')->avg('days_outstanding') ?? 0,
            'overdue_count' => $items->where('status', 'overdue')->count(),
            'recent_submissions' => $items->filter(function($item) {
                return $item->date_submitted && $item->date_submitted >= now()->subDays(7);
            })->count(),
        ];
    }

    public function markMultipleAsOverdue(): int
    {
        DB::beginTransaction();

        try {
            $overdueItems = PbcRequestItem::where('due_date', '<', now())
                ->whereNotIn('status', ['accepted', 'overdue'])
                ->get();

            $count = 0;
            foreach ($overdueItems as $item) {
                $item->update([
                    'status' => 'overdue',
                    'days_outstanding' => now()->diffInDays($item->date_requested)
                ]);
                $count++;
            }

            // Update progress for affected requests
            $affectedRequests = PbcRequest::whereIn('id', $overdueItems->pluck('pbc_request_id')->unique())->get();
            foreach ($affectedRequests as $request) {
                $request->updateProgress();
            }

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateAllDaysOutstanding(): int
    {
        DB::beginTransaction();

        try {
            $activeItems = PbcRequestItem::whereNotIn('status', ['accepted'])
                ->whereNotNull('date_requested')
                ->get();

            $count = 0;
            foreach ($activeItems as $item) {
                $oldDays = $item->days_outstanding;
                $newDays = now()->diffInDays($item->date_requested);

                if ($oldDays !== $newDays) {
                    $item->update(['days_outstanding' => $newDays]);
                    $count++;
                }
            }

            DB::commit();
            return $count;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getItemsRequiringAttention(int $userId): array
    {
        $user = auth()->user();

        $baseQuery = PbcRequestItem::with(['pbcRequest.project.client', 'category']);

        // Apply user access control
        if ($user->isGuest()) {
            $baseQuery->where('assigned_to', $userId);
        } elseif (!$user->isSystemAdmin()) {
            $baseQuery->whereHas('pbcRequest.project', function($projectQuery) use ($user) {
                $projectQuery->where(function($q) use ($user) {
                    $q->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
                });
            });
        }

        return [
            'overdue' => (clone $baseQuery)->where('status', 'overdue')->count(),
            'due_today' => (clone $baseQuery)->whereDate('due_date', now()->toDateString())
                ->whereNotIn('status', ['accepted'])->count(),
            'due_this_week' => (clone $baseQuery)->whereBetween('due_date', [now(), now()->addDays(7)])
                ->whereNotIn('status', ['accepted'])->count(),
            'pending_review' => (clone $baseQuery)->where('status', 'submitted')
                ->where(function($query) use ($userId) {
                    $query->where('assigned_to', $userId)
                          ->orWhereHas('pbcRequest.project', function($projectQuery) use ($userId) {
                              $projectQuery->where('engagement_partner_id', $userId)
                                          ->orWhere('manager_id', $userId);
                          });
                })->count(),
            'rejected_items' => (clone $baseQuery)->where('status', 'rejected')
                ->where('assigned_to', $userId)->count(),
        ];
    }

    public function assignItemsToUser(array $itemIds, int $userId, ?string $note = null): array
    {
        DB::beginTransaction();

        try {
            $items = PbcRequestItem::whereIn('id', $itemIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($items as $item) {
                try {
                    if (!$item->canBeEditedBy(auth()->user())) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for item: {$item->getDisplayName()}";
                        continue;
                    }

                    $item->update(['assigned_to' => $userId]);

                    if ($note) {
                        $item->update(['client_remarks' => $note]);
                    }

                    AuditLog::logPbcActivity('assigned', $item,
                        "PBC item assigned to user ID: {$userId}", auth()->user());

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to assign item '{$item->getDisplayName()}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getItemsByStatus(string $status, array $filters = []): LengthAwarePaginator
    {
        $filters = array_merge($filters, ['status' => $status]);
        return $this->getFilteredItems($filters);
    }

    public function createItemFromTemplate(PbcRequest $pbcRequest, int $templateItemId, array $overrides = []): PbcRequestItem
    {
        DB::beginTransaction();

        try {
            $templateItem = \App\Models\PbcTemplateItem::findOrFail($templateItemId);

            $itemData = array_merge([
                'pbc_request_id' => $pbcRequest->id,
                'template_item_id' => $templateItemId,
                'category_id' => $templateItem->category_id,
                'item_number' => $templateItem->item_number,
                'sub_item_letter' => $templateItem->sub_item_letter,
                'description' => $templateItem->description,
                'sort_order' => $templateItem->sort_order,
                'status' => 'pending',
                'date_requested' => now(),
                'requested_by' => auth()->id(),
                'is_required' => $templateItem->is_required,
                'is_custom' => false,
            ], $overrides);

            $item = PbcRequestItem::create($itemData);

            // Update parent request progress
            $pbcRequest->updateProgress();

            // Log activity
            AuditLog::logPbcActivity('created', $item,
                "PBC item created from template item", auth()->user());

            DB::commit();
            return $item->load(['category', 'pbcRequest']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getCompletionTrends(PbcRequest $pbcRequest, int $days = 30): array
    {
        $items = $pbcRequest->items()
            ->where('date_reviewed', '>=', now()->subDays($days))
            ->where('status', 'accepted')
            ->get();

        $trends = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $completedOnDate = $items->filter(function($item) use ($date) {
                return $item->date_reviewed && $item->date_reviewed->toDateString() === $date;
            })->count();

            $trends[] = [
                'date' => $date,
                'completed_count' => $completedOnDate,
            ];
        }

        return $trends;
    }
}
