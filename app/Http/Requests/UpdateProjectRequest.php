<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_project');
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'exists:clients,id'],
            'engagement_type' => ['sometimes', 'required', Rule::in(['audit', 'accounting', 'tax', 'special_engagement', 'others'])],
            'engagement_period' => ['sometimes', 'required', 'date'],
            'contact_person' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'engagement_partner_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'associate_1_id' => ['nullable', 'exists:users,id'],
            'associate_2_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['active', 'completed', 'on_hold', 'cancelled'])],
            'progress_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
