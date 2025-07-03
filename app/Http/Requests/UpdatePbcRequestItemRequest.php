<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePbcRequestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_pbc_request');
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['pending', 'submitted', 'under_review', 'accepted', 'rejected', 'overdue'])],
            'remarks' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'client_remarks' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'item_number' => ['sometimes', 'nullable', 'string', 'max:10'],
            'sub_item_letter' => ['sometimes', 'nullable', 'string', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Item description is required.',
            'description.max' => 'Item description cannot exceed 1000 characters.',
            'due_date.date' => 'Please enter a valid due date.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'status.in' => 'Invalid status selected.',
            'remarks.max' => 'Remarks cannot exceed 1000 characters.',
            'client_remarks.max' => 'Client remarks cannot exceed 1000 characters.',
            'sort_order.integer' => 'Sort order must be a number.',
            'sort_order.min' => 'Sort order cannot be negative.',
            'item_number.max' => 'Item number cannot exceed 10 characters.',
            'sub_item_letter.max' => 'Sub-item letter cannot exceed 5 characters.',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert empty strings to null for nullable fields
        $nullableFields = ['assigned_to', 'due_date', 'remarks', 'client_remarks', 'item_number', 'sub_item_letter'];

        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }

        // Convert string boolean values
        if ($this->has('is_required')) {
            $this->merge([
                'is_required' => filter_var($this->is_required, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        }

        // Ensure sort_order is integer if provided
        if ($this->has('sort_order')) {
            $this->merge([
                'sort_order' => (int) $this->sort_order
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation logic can be added here

            // Check if the item can be modified based on its current status
            if ($this->route('pbcRequestItem')) {
                $item = $this->route('pbcRequestItem');

                // Prevent status changes if user doesn't have review permissions
                if ($this->has('status') && $this->status !== $item->status) {
                    if (in_array($this->status, ['accepted', 'rejected']) && !$item->canBeReviewedBy($this->user())) {
                        $validator->errors()->add('status', 'You do not have permission to change the status to ' . $this->status);
                    }
                }

                // Prevent editing of non-custom items by non-staff users
                if (!$item->is_custom && $this->user()->isGuest()) {
                    if ($this->has('description') || $this->has('is_required') || $this->has('sort_order')) {
                        $validator->errors()->add('description', 'Template-based items cannot be modified by clients.');
                    }
                }
            }
        });
    }
}
