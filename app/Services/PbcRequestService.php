<?php

namespace App\Services;

use App\Models\PbcRequest;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PbcRequestService
{
    public function getFilteredPbcRequests(array $filters, User $user): LengthAwarePaginator
    {
        $query = PbcRequest::with(['project.client', 'category', 'requestor', 'assignedTo', 'documents'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('project.client', function ($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'overdue') {
                    $query->overdue();
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($filters['priority'] ?? null, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['project_id'] ?? null, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($filters['assigned_to_id'] ?? null, function ($query, $assignedToId) {
                $query->where('assigned_to_id', $assignedToId);
            })
            ->when($filters['due_date_from'] ?? null, function ($query, $dateFrom) {
                $query->where('due_date', '>=', $dateFrom);
            })
            ->when($filters['due_date_to'] ?? null, function ($query, $dateTo) {
                $query->where('due_date', '<=', $dateTo);
            });

        // Apply user-based filtering
        if ($user->isGuest()) {
            $query->where('assigned_to_id', $user->id);
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereIn('project_id', $projectIds);
        }

        $query->orderBy($filters['sort_by'] ?? 'due_date', $filters['sort_order'] ?? 'asc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createPbcRequest(array $data, User $requestor): PbcRequest
    {
        $data['requestor_id'] = $requestor->id;
        $data['date_requested'] = now();
        $data['status'] = 'pending';

        $pbcRequest = PbcRequest::create($data);

        // Log activity
        $this->logActivity('pbc_request_created', $pbcRequest, $requestor, 'PBC request created');

        return $pbcRequest->load(['project.client', 'category', 'requestor', 'assignedTo']);
    }

    public function updatePbcRequest(PbcRequest $pbcRequest, array $data, User $user): PbcRequest
    {
        $oldData = $pbcRequest->toArray();
        $pbcRequest->update($data);

        // Log activity
        $this->logActivity('pbc_request_updated', $pbcRequest, $user, 'PBC request updated', $oldData);

        return $pbcRequest->fresh(['project.client', 'category', 'requestor', 'assignedTo']);
    }

    public function deletePbcRequest(PbcRequest $pbcRequest): bool
    {
        $this->logActivity('pbc_request_deleted', $pbcRequest, auth()->user(), 'PBC request deleted');

        return $pbcRequest->delete();
    }

    public function completePbcRequest(PbcRequest $pbcRequest, User $user): PbcRequest
    {
        $pbcRequest->markAsCompleted($user->id);

        // Update project progress
        $pbcRequest->project->updateProgress();

        $this->logActivity('pbc_request_completed', $pbcRequest, $user, 'PBC request marked as completed');

        return $pbcRequest;
    }

    public function reopenPbcRequest(PbcRequest $pbcRequest, User $user): PbcRequest
    {
        $pbcRequest->update([
            'status' => 'pending',
            'completed_at' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        // Update project progress
        $pbcRequest->project->updateProgress();

        $this->logActivity('pbc_request_reopened', $pbcRequest, $user, 'PBC request reopened');

        return $pbcRequest;
    }

    public function bulkUpdatePbcRequests(array $pbcRequestIds, string $action, User $user, ?int $assignedToId = null): array
    {
        $updated = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($pbcRequestIds as $id) {
                $pbcRequest = PbcRequest::find($id);

                if (!$pbcRequest) {
                    $errors[] = "PBC request with ID {$id} not found";
                    continue;
                }

                switch ($action) {
                    case 'complete':
                        $this->completePbcRequest($pbcRequest, $user);
                        $updated++;
                        break;

                    case 'reopen':
                        $this->reopenPbcRequest($pbcRequest, $user);
                        $updated++;
                        break;

                    case 'delete':
                        $this->deletePbcRequest($pbcRequest);
                        $updated++;
                        break;

                    case 'assign':
                        if ($assignedToId) {
                            $pbcRequest->update(['assigned_to_id' => $assignedToId]);
                            $this->logActivity('pbc_request_reassigned', $pbcRequest, $user, 'PBC request reassigned');
                            $updated++;
                        } else {
                            $errors[] = "No assignee specified for PBC request {$id}";
                        }
                        break;

                    default:
                        $errors[] = "Invalid action: {$action}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return [
            'updated' => $updated,
            'errors' => $errors,
            'total' => count($pbcRequestIds)
        ];
    }

    private function getUserProjectIds(User $user): array
    {
        return \App\Models\Project::where(function ($query) use ($user) {
            $query->where('engagement_partner_id', $user->id)
                  ->orWhere('manager_id', $user->id)
                  ->orWhere('associate_1_id', $user->id)
                  ->orWhere('associate_2_id', $user->id);
        })->pluck('id')->toArray();
    }

    private function logActivity(string $action, PbcRequest $pbcRequest, User $user, string $description, array $oldData = null): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => PbcRequest::class,
            'model_id' => $pbcRequest->id,
            'old_values' => $oldData,
            'new_values' => $pbcRequest->toArray(),
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
