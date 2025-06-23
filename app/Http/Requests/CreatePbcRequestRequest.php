<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePbcRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_pbc_request');
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'category_id' => ['required', 'exists:pbc_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'assigned_to_id' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date', 'after:today'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.exists' => 'Selected project does not exist.',
            'category_id.exists' => 'Selected category does not exist.',
            'assigned_to_id.exists' => 'Selected user does not exist.',
            'due_date.after' => 'Due date must be in the future.',
        ];
    }
}
