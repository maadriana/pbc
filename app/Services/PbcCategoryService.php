<?php

namespace App\Services;

use App\Models\PbcCategory;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

class PbcCategoryService
{
    public function getFilteredCategories(array $filters): LengthAwarePaginator
    {
        $query = PbcCategory::withCount(['pbcRequests', 'templates'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_order'] ?? 'asc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createCategory(array $data): PbcCategory
    {
        $category = PbcCategory::create($data);

        $this->logActivity('category_created', $category, 'Category created');

        return $category;
    }

    public function updateCategory(PbcCategory $category, array $data): PbcCategory
    {
        $oldData = $category->toArray();
        $category->update($data);

        $this->logActivity('category_updated', $category, 'Category updated', $oldData);

        return $category->fresh();
    }

    public function deleteCategory(PbcCategory $category): bool
    {
        $this->logActivity('category_deleted', $category, 'Category deleted');

        return $category->delete();
    }

    private function logActivity(string $action, PbcCategory $category, string $description, array $oldData = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => PbcCategory::class,
            'model_id' => $category->id,
            'old_values' => $oldData,
            'new_values' => $category->toArray(),
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
