<?php

namespace App\Services;

use App\Models\PbcCategory;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PbcCategoryService
{
    public function getFilteredCategories(array $filters): LengthAwarePaginator
    {
        $query = PbcCategory::withCount(['templateItems', 'requestItems']);

        // Apply filters
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        })
        ->when($filters['is_active'] ?? null, function ($query, $isActive) {
            $query->where('is_active', $isActive);
        });

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createCategory(array $categoryData): PbcCategory
    {
        DB::beginTransaction();

        try {
            // Auto-generate sort order if not provided
            if (!isset($categoryData['sort_order'])) {
                $maxOrder = PbcCategory::max('sort_order') ?? 0;
                $categoryData['sort_order'] = $maxOrder + 1;
            }

            $category = PbcCategory::create($categoryData);

            // Log activity
            AuditLog::logPbcActivity('created', $category,
                "PBC category '{$category->name}' created", auth()->user());

            DB::commit();
            return $category;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateCategory(PbcCategory $category, array $categoryData): PbcCategory
    {
        DB::beginTransaction();

        try {
            // Store old values for audit log
            $oldValues = $category->toArray();

            // Update the category
            $category->update($categoryData);

            // Log activity
            AuditLog::logModelChange('updated', $category, $oldValues, $category->fresh()->toArray());

            DB::commit();
            return $category->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deleteCategory(PbcCategory $category): bool
    {
        DB::beginTransaction();

        try {
            // Check if category is being used
            $templateItemsCount = $category->templateItems()->count();
            $requestItemsCount = $category->requestItems()->count();

            if ($templateItemsCount > 0 || $requestItemsCount > 0) {
                throw new \Exception("Cannot delete category. It is being used by {$templateItemsCount} template items and {$requestItemsCount} request items.");
            }

            // Log activity before deletion
            AuditLog::logPbcActivity('deleted', $category,
                "PBC category '{$category->name}' deleted", auth()->user());

            // Delete the category
            $result = $category->delete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function reorderCategories(array $categoryOrders): array
    {
        DB::beginTransaction();

        try {
            $updated = 0;

            foreach ($categoryOrders as $order) {
                if (isset($order['id']) && isset($order['sort_order'])) {
                    $category = PbcCategory::find($order['id']);
                    if ($category) {
                        $category->update(['sort_order' => $order['sort_order']]);
                        $updated++;
                    }
                }
            }

            // Log activity
            AuditLog::logPbcActivity('reordered', null,
                "PBC categories reordered ({$updated} categories affected)", auth()->user());

            DB::commit();
            return ['updated' => $updated];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function toggleStatus(PbcCategory $category): PbcCategory
    {
        DB::beginTransaction();

        try {
            $newStatus = !$category->is_active;
            $category->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'activated' : 'deactivated';

            // Log activity
            AuditLog::logPbcActivity($statusText, $category,
                "PBC category '{$category->name}' {$statusText}", auth()->user());

            DB::commit();
            return $category->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getCategoryUsageStats(PbcCategory $category): array
    {
        return [
            'template_items_count' => $category->templateItems()->count(),
            'request_items_count' => $category->requestItems()->count(),
            'active_request_items_count' => $category->requestItems()
                ->whereHas('pbcRequest', function($query) {
                    $query->where('status', 'active');
                })->count(),
            'recent_usage' => $category->requestItems()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    public function duplicateCategory(PbcCategory $category, string $newName): PbcCategory
    {
        DB::beginTransaction();

        try {
            $newCategory = $category->replicate();
            $newCategory->name = $newName;
            $newCategory->code = \Str::slug($newName, '_');

            // Ensure unique code
            $baseCode = $newCategory->code;
            $counter = 1;
            while (PbcCategory::where('code', $newCategory->code)->exists()) {
                $newCategory->code = $baseCode . '_' . $counter;
                $counter++;
            }

            // Set new sort order
            $maxOrder = PbcCategory::max('sort_order') ?? 0;
            $newCategory->sort_order = $maxOrder + 1;

            $newCategory->save();

            // Log activity
            AuditLog::logPbcActivity('duplicated', $newCategory,
                "PBC category duplicated from '{$category->name}' to '{$newCategory->name}'", auth()->user());

            DB::commit();
            return $newCategory;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
