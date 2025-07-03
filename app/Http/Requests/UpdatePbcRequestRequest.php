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
            'title' => ['sometimes', 'string', 'max:255'],
            'contact_person' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'engagement_partner' => ['sometimes', 'string', 'max:255'],
            'engagement_manager' => ['sometimes', 'string', 'max:255'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'client_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'contact_email.email' => 'Please enter a valid email address.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert empty strings to null for nullable fields
        $nullableFields = ['assigned_to', 'due_date', 'notes', 'client_notes', 'status_note'];

        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
