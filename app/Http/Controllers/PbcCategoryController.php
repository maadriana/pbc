<?php

namespace App\Http\Controllers;

use App\Models\PbcCategory;
use App\Services\PbcCategoryService;
use Illuminate\Http\Request;

class PbcCategoryController extends BaseController
{
    protected $pbcCategoryService;

    public function __construct(PbcCategoryService $pbcCategoryService)
    {
        $this->pbcCategoryService = $pbcCategoryService;
    }

    public function index(Request $request)
    {
        try {
            $categories = $this->pbcCategoryService->getFilteredCategories($request->all());
            return $this->paginated($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve categories', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Use custom permission check instead of $this->authorize()
            if (!auth()->user()->hasPermission('manage_categories')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:pbc_categories,code',
                'description' => 'nullable|string',
                'color_code' => 'required|string|max:7',
                'is_active' => 'boolean',
            ]);

            $category = $this->pbcCategoryService->createCategory($request->validated());
            return $this->success($category, 'Category created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create category', $e->getMessage(), 500);
        }
    }

    public function show(PbcCategory $category)
    {
        try {
            $category->load(['pbcRequests', 'templates']);
            return $this->success($category, 'Category retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve category', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, PbcCategory $category)
    {
        try {
            // Use custom permission check instead of $this->authorize()
            if (!auth()->user()->hasPermission('manage_categories')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'sometimes|required|string|max:20|unique:pbc_categories,code,' . $category->id,
                'description' => 'nullable|string',
                'color_code' => 'sometimes|required|string|max:7',
                'is_active' => 'boolean',
            ]);

            $updatedCategory = $this->pbcCategoryService->updateCategory($category, $request->validated());
            return $this->success($updatedCategory, 'Category updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update category', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcCategory $category)
    {
        try {
            // Use custom permission check instead of $this->authorize()
            if (!auth()->user()->hasPermission('manage_categories')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->pbcCategoryService->deleteCategory($category);
            return $this->success(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete category', $e->getMessage(), 500);
        }
    }
}
