<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_project');
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'engagement_type' => ['required', Rule::in(['audit', 'accounting', 'tax', 'special_engagement', 'others'])],
            'engagement_period' => ['required', 'date'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'engagement_partner_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'associate_1_id' => ['nullable', 'exists:users,id'],
            'associate_2_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['active', 'completed', 'on_hold', 'cancelled'])],
            'progress_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Selected client does not exist.',
            'engagement_partner_id.exists' => 'Selected engagement partner does not exist.',
            'manager_id.exists' => 'Selected manager does not exist.',
            'associate_1_id.exists' => 'Selected associate 1 does not exist.',
            'associate_2_id.exists' => 'Selected associate 2 does not exist.',
        ];
    }
}
