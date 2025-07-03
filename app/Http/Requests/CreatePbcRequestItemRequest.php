<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePbcRequestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_pbc_request');
    }

    public function rules(): array
    {
        return [
            'pbc_request_id' => ['required', 'exists:pbc_requests,id'],
            'category_id' => ['required', 'exists:pbc_categories,id'],
            'parent_id' => ['nullable', 'exists:pbc_request_items,id'],
            'description' => ['required', 'string', 'max:1000'],
            'item_number' => ['nullable', 'string', 'max:10'],
            'sub_item_letter' => ['nullable', 'string', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'is_required' => ['boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'client_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'pbc_request_id.required' => 'PBC request is required.',
            'pbc_request_id.exists' => 'Selected PBC request does not exist.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'parent_id.exists' => 'Selected parent item does not exist.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'due_date.after' => 'Due date must be in the future.',
        ];
    }

    protected function prepareForValidation()
    {
        // Set defaults
        if (!$this->has('is_required')) {
            $this->merge(['is_required' => false]);
        }

        // Convert empty strings to null
        $nullableFields = ['parent_id', 'assigned_to', 'due_date', 'item_number', 'sub_item_letter', 'remarks', 'client_remarks'];

        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }

        // Auto-generate sort order if not provided
        if (!$this->has('sort_order') || $this->sort_order === null) {
            $maxOrder = \App\Models\PbcRequestItem::where('pbc_request_id', $this->pbc_request_id)
                ->where('category_id', $this->category_id)
                ->max('sort_order') ?? 0;
            $this->merge(['sort_order' => $maxOrder + 1]);
        }
    }
}
