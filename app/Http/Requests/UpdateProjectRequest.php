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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert empty strings to null for optional foreign keys
        $fieldsToNullify = ['engagement_partner_id', 'manager_id', 'associate_1_id', 'associate_2_id'];

        foreach ($fieldsToNullify as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }

        // Convert progress_percentage to proper decimal
        if ($this->has('progress_percentage')) {
            $progress = $this->progress_percentage;
            if ($progress === '' || $progress === null) {
                $this->merge(['progress_percentage' => 0]);
            } else {
                $this->merge(['progress_percentage' => (float) $progress]);
            }
        }
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Selected client does not exist.',
            'engagement_type.required' => 'Engagement type is required.',
            'engagement_type.in' => 'Invalid engagement type selected.',
            'engagement_period.required' => 'Engagement period is required.',
            'engagement_period.date' => 'Engagement period must be a valid date.',
            'contact_person.required' => 'Contact person is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Contact email must be a valid email address.',
            'contact_number.required' => 'Contact number is required.',
            'engagement_partner_id.exists' => 'Selected engagement partner does not exist.',
            'manager_id.exists' => 'Selected manager does not exist.',
            'associate_1_id.exists' => 'Selected associate 1 does not exist.',
            'associate_2_id.exists' => 'Selected associate 2 does not exist.',
            'status.in' => 'Invalid status selected.',
            'progress_percentage.numeric' => 'Progress percentage must be a number.',
            'progress_percentage.between' => 'Progress percentage must be between 0 and 100.',
        ];
    }
}
