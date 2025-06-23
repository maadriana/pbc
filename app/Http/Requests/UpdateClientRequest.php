<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_client');
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sec_registration_no' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('clients')->ignore($clientId)],
            'industry_classification' => ['sometimes', 'required', 'string', 'max:100'],
            'business_address' => ['sometimes', 'required', 'string'],
            'primary_contact_name' => ['sometimes', 'required', 'string', 'max:255'],
            'primary_contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'primary_contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_email' => ['nullable', 'email', 'max:255'],
            'secondary_contact_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
