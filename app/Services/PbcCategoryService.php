<?php

namespace App\Services;

use App\Models\PbcCategory;
use Illuminate\Pagination\LengthAwarePaginator;

class PbcCategoryService
{
    public function getFilteredCategories(array $filters): LengthAwarePaginator
    {
        $query = PbcCategory::query()
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
        return PbcCategory::create($data);
    }

    public function updateCategory(PbcCategory $category, array $data): PbcCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(PbcCategory $category): bool
    {
        return $category->delete();
    }
}
