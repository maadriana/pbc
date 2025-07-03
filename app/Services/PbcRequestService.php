<?php

namespace App\Services;

use App\Models\PbcRequest;
use App\Models\PbcTemplate;
use App\Models\Project;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PbcRequestService
{
    public function getFilteredPbcRequests(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = PbcRequest::with([
            'project.client',
            'template',
            'creator',
            'assignedTo'
        ]);

        // Apply access control based on user role
        if ($user->isGuest()) {
            // Guests can only see requests assigned to them or their client projects
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('project', function($projectQuery) use ($user) {
                      $projectQuery->where('contact_email', $user->email);
                  });
            });
        } elseif (!$user->isSystemAdmin()) {
            // Staff can see requests for projects they're assigned to
            $query->whereHas('project', function($projectQuery) use ($user) {
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
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('status_note', 'like', "%{$search}%")
                  ->orWhereHas('project.client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        })
        ->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($filters['project_id'] ?? null, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })
        ->when($filters['assigned_to'] ?? null, function ($query, $assignedTo) {
            $query->where('assigned_to', $assignedTo);
        })
        ->when($filters['template_id'] ?? null, function ($query, $templateId) {
            $query->where('template_id', $templateId);
        })
        ->when($filters['overdue'] ?? null, function ($query) {
            $query->where('due_date', '<', now())
                  ->whereIn('status', ['draft', 'active']);
        })
        ->when($filters['client_id'] ?? null, function ($query, $clientId) {
            $query->whereHas('project', function($projectQuery) use ($clientId) {
                $projectQuery->where('client_id', $clientId);
            });
        })
        ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        })
        ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            $query->where('created_at', '<=', $dateTo);
        });

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createPbcRequest(array $pbcRequestData): PbcRequest
    {
        DB::beginTransaction();

        try {
            // Get project and template
            $project = Project::with('client')->findOrFail($pbcRequestData['project_id']);
            $template = PbcTemplate::findOrFail($pbcRequestData['template_id']);

            // Check if template can be used for this engagement type
            if (!$template->canBeUsedForEngagement($project->engagement_type)) {
                throw new \Exception('Selected template cannot be used for this engagement type');
            }

            // Prepare request data with project defaults
            $requestData = array_merge([
                'title' => "{$template->name} - {$project->client->name} {$project->engagement_period->format('Y')}",
                'client_name' => $project->client->name,
                'audit_period' => $project->engagement_period->format('Y-m-d'),
                'contact_person' => $project->contact_person,
                'contact_email' => $project->contact_email,
                'engagement_partner' => $project->engagementPartner?->name,
                'engagement_manager' => $project->manager?->name,
                'document_date' => now(),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ], $pbcRequestData);

            // Create the PBC request
            $pbcRequest = PbcRequest::create($requestData);

            // Create request items from template
            $this->createRequestItemsFromTemplate($pbcRequest, $template);

            // Update progress
            $pbcRequest->updateProgress();

            // Update project progress
            $project->updatePbcProgress();

            // Log activity
            AuditLog::logPbcActivity('created', $pbcRequest,
                "PBC request '{$pbcRequest->title}' created", auth()->user());

            DB::commit();
            return $pbcRequest->load(['project.client', 'template', 'creator', 'assignedTo']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updatePbcRequest(PbcRequest $pbcRequest, array $pbcRequestData): PbcRequest
    {
        DB::beginTransaction();

        try {
            // Store old values for audit log
            $oldValues = $pbcRequest->toArray();

            // Update the request
            $pbcRequest->update($pbcRequestData);

            // Update progress if items might have changed
            $pbcRequest->updateProgress();

            // Log activity
            AuditLog::logModelChange('updated', $pbcRequest, $oldValues, $pbcRequest->fresh()->toArray());

            DB::commit();
            return $pbcRequest->fresh(['project.client', 'template', 'creator', 'assignedTo']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deletePbcRequest(PbcRequest $pbcRequest): bool
    {
        DB::beginTransaction();

        try {
            // Log activity before deletion
            AuditLog::logPbcActivity('deleted', $pbcRequest,
                "PBC request '{$pbcRequest->title}' deleted", auth()->user());

            // Soft delete the request (this will cascade to items via model events)
            $result = $pbcRequest->delete();

            // Update project progress
            $pbcRequest->project->updatePbcProgress();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function markAsCompleted(PbcRequest $pbcRequest): PbcRequest
    {
        DB::beginTransaction();

        try {
            // Check if all required items are accepted
            $pendingRequiredItems = $pbcRequest->items()
                ->where('is_required', true)
                ->whereNotIn('status', ['accepted'])
                ->count();

            if ($pendingRequiredItems > 0) {
                throw new \Exception("Cannot complete request. {$pendingRequiredItems} required items are still pending.");
            }

            $pbcRequest->markAsCompleted();

            // Update project progress
            $pbcRequest->project->updatePbcProgress();

            // Log activity
            AuditLog::logPbcActivity('completed', $pbcRequest,
                "PBC request '{$pbcRequest->title}' marked as completed", auth()->user());

            DB::commit();
            return $pbcRequest->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function reopenRequest(PbcRequest $pbcRequest): PbcRequest
    {
        DB::beginTransaction();

        try {
            $pbcRequest->update([
                'status' => 'active',
                'completed_at' => null,
            ]);

            $pbcRequest->updateProgress();

            // Log activity
            AuditLog::logPbcActivity('reopened', $pbcRequest,
                "PBC request '{$pbcRequest->title}' reopened", auth()->user());

            DB::commit();
            return $pbcRequest->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkUpdateRequests(array $requestIds, string $action, array $data = []): array
    {
        DB::beginTransaction();

        try {
            $requests = PbcRequest::whereIn('id', $requestIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($requests as $request) {
                try {
                    // Check permissions for each request
                    if (!$request->canBeEditedBy(auth()->user())) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for request: {$request->title}";
                        continue;
                    }

                    switch ($action) {
                        case 'complete':
                            $this->markAsCompleted($request);
                            break;
                        case 'reopen':
                            $this->reopenRequest($request);
                            break;
                        case 'delete':
                            $this->deletePbcRequest($request);
                            break;
                        case 'assign':
                            if (isset($data['assigned_to'])) {
                                $request->update(['assigned_to' => $data['assigned_to']]);
                            }
                            break;
                        case 'update_due_date':
                            if (isset($data['due_date'])) {
                                $request->update(['due_date' => $data['due_date']]);
                            }
                            break;
                        default:
                            throw new \Exception("Unknown action: {$action}");
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to {$action} request '{$request->title}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getDetailedProgress(PbcRequest $pbcRequest): array
    {
        $items = $pbcRequest->items()->with('category')->get();

        $progress = [
            'total_items' => $items->count(),
            'completed_items' => $items->where('status', 'accepted')->count(),
            'pending_items' => $items->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'rejected_items' => $items->where('status', 'rejected')->count(),
            'overdue_items' => $items->where('status', 'overdue')->count(),
            'completion_percentage' => $pbcRequest->completion_percentage,
            'by_category' => [],
            'by_status' => [],
            'recent_activity' => [],
        ];

        // Group by category
        foreach ($items->groupBy('category.name') as $categoryName => $categoryItems) {
            $progress['by_category'][$categoryName] = [
                'total' => $categoryItems->count(),
                'completed' => $categoryItems->where('status', 'accepted')->count(),
                'pending' => $categoryItems->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
                'completion_rate' => $categoryItems->count() > 0
                    ? ($categoryItems->where('status', 'accepted')->count() / $categoryItems->count()) * 100
                    : 0,
            ];
        }

        // Group by status
        foreach ($items->groupBy('status') as $status => $statusItems) {
            $progress['by_status'][$status] = $statusItems->count();
        }

        // Get recent activity (last 10 audit logs)
        $progress['recent_activity'] = AuditLog::where('model_type', PbcRequest::class)
            ->where('model_id', $pbcRequest->id)
            ->orWhere(function($query) use ($pbcRequest) {
                $query->where('model_type', 'App\\Models\\PbcRequestItem')
                      ->whereIn('model_id', $pbcRequest->items()->pluck('id'));
            })
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->getUserDisplayName(),
                    'created_at' => $log->created_at->diffForHumans(),
                ];
            });

        return $progress;
    }

    public function getAvailableTemplates(?string $engagementType = null, ?int $projectId = null): array
    {
        $query = PbcTemplate::where('is_active', true);

        if ($engagementType) {
            $query->where(function($q) use ($engagementType) {
                $q->whereJsonContains('engagement_types', $engagementType)
                  ->orWhereNull('engagement_types');
            });
        }

        if ($projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $query->where(function($q) use ($project) {
                    $q->whereJsonContains('engagement_types', $project->engagement_type)
                      ->orWhereNull('engagement_types');
                });
            }
        }

        return $query->orderBy('is_default', 'desc')
                    ->orderBy('name')
                    ->get()
                    ->toArray();
    }

    public function duplicateRequest(PbcRequest $pbcRequest, array $overrides = []): PbcRequest
    {
        DB::beginTransaction();

        try {
            // Prepare data for duplication
            $newData = array_merge([
                'project_id' => $pbcRequest->project_id,
                'template_id' => $pbcRequest->template_id,
                'title' => $pbcRequest->title . ' (Copy)',
                'client_name' => $pbcRequest->client_name,
                'audit_period' => $pbcRequest->audit_period,
                'contact_person' => $pbcRequest->contact_person,
                'contact_email' => $pbcRequest->contact_email,
                'engagement_partner' => $pbcRequest->engagement_partner,
                'engagement_manager' => $pbcRequest->engagement_manager,
                'document_date' => now(),
                'status' => 'draft',
                'created_by' => auth()->id(),
                'notes' => $pbcRequest->notes,
                'client_notes' => $pbcRequest->client_notes,
            ], $overrides);

            // Create new request
            $newRequest = PbcRequest::create($newData);

            // Duplicate all items
            foreach ($pbcRequest->items as $item) {
                $newItem = $item->duplicate($newRequest->id, auth()->id());
            }

            // Update progress
            $newRequest->updateProgress();

            // Log activity
            AuditLog::logPbcActivity('duplicated', $newRequest,
                "PBC request duplicated from '{$pbcRequest->title}'", auth()->user());

            DB::commit();
            return $newRequest->load(['project.client', 'template', 'items']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function exportRequests(string $format, ?array $requestIds = null): array
    {
        // This would implement export functionality
        // For now, return a placeholder

        $query = PbcRequest::with(['project.client', 'template', 'items']);

        if ($requestIds) {
            $query->whereIn('id', $requestIds);
        }

        $requests = $query->get();

        // Generate export file based on format
        $filename = 'pbc_requests_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        switch ($format) {
            case 'excel':
                // Implement Excel export
                $this->exportToExcel($requests, $filePath);
                break;
            case 'pdf':
                // Implement PDF export
                $this->exportToPdf($requests, $filePath);
                break;
            case 'csv':
                // Implement CSV export
                $this->exportToCsv($requests, $filePath);
                break;
        }

        return [
            'file_path' => $filePath,
            'filename' => $filename,
        ];
    }

    private function createRequestItemsFromTemplate(PbcRequest $pbcRequest, PbcTemplate $template): void
    {
        $templateItems = $template->templateItems()
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        foreach ($templateItems as $templateItem) {
            \App\Models\PbcRequestItem::create([
                'pbc_request_id' => $pbcRequest->id,
                'template_item_id' => $templateItem->id,
                'category_id' => $templateItem->category_id,
                'parent_id' => null, // Will be set after all items are created
                'item_number' => $templateItem->item_number,
                'sub_item_letter' => $templateItem->sub_item_letter,
                'description' => $templateItem->description,
                'sort_order' => $templateItem->sort_order,
                'status' => 'pending',
                'date_requested' => now(),
                'due_date' => $pbcRequest->due_date,
                'requested_by' => auth()->id(),
                'assigned_to' => $pbcRequest->assigned_to,
                'is_required' => $templateItem->is_required,
                'is_custom' => false,
            ]);
        }

        // Update parent-child relationships
        $this->updateParentChildRelationships($pbcRequest);
    }

    private function updateParentChildRelationships(PbcRequest $pbcRequest): void
    {
        $requestItems = $pbcRequest->items()->get();
        $templateToRequestMap = [];

        // Create mapping
        foreach ($requestItems as $item) {
            if ($item->template_item_id) {
                $templateToRequestMap[$item->template_item_id] = $item->id;
            }
        }

        // Update parent relationships
        foreach ($requestItems as $item) {
            if ($item->template_item_id && $item->templateItem && $item->templateItem->parent_id) {
                $parentRequestItemId = $templateToRequestMap[$item->templateItem->parent_id] ?? null;
                if ($parentRequestItemId) {
                    $item->update(['parent_id' => $parentRequestItemId]);
                }
            }
        }
    }

    private function exportToExcel($requests, $filePath): void
    {
        // Placeholder for Excel export implementation
        file_put_contents($filePath, "Excel export not yet implemented");
    }

    private function exportToPdf($requests, $filePath): void
    {
        // Placeholder for PDF export implementation
        file_put_contents($filePath, "PDF export not yet implemented");
    }

    private function exportToCsv($requests, $filePath): void
    {
        // Placeholder for CSV export implementation
        file_put_contents($filePath, "CSV export not yet implemented");
    }
}
