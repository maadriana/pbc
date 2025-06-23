<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePbcRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_pbc_request');
    }

    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'exists:projects,id'],
            'category_id' => ['sometimes', 'required', 'exists:pbc_categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'assigned_to_id' => ['sometimes', 'required', 'exists:users,id'],
            'due_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'overdue', 'rejected'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }
}
