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
            'template_id' => ['required', 'exists:pbc_templates,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'engagement_partner' => ['nullable', 'string', 'max:255'],
            'engagement_manager' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'client_notes' => ['nullable', 'string', 'max:2000'],
            'status_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'Selected project does not exist.',
            'template_id.required' => 'Please select a template.',
            'template_id.exists' => 'Selected template does not exist.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'due_date.after' => 'Due date must be in the future.',
            'contact_email.email' => 'Please enter a valid email address.',
        ];
    }

    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }

        // Convert empty strings to null for optional fields
        $nullableFields = ['assigned_to', 'due_date', 'title', 'notes', 'client_notes', 'status_note'];

        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
