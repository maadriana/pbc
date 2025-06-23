<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_client');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sec_registration_no' => ['required', 'string', 'max:50', 'unique:clients,sec_registration_no'],
            'industry_classification' => ['required', 'string', 'max:100'],
            'business_address' => ['required', 'string'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'primary_contact_email' => ['required', 'email', 'max:255'],
            'primary_contact_number' => ['required', 'string', 'max:20'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_email' => ['nullable', 'email', 'max:255'],
            'secondary_contact_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sec_registration_no.unique' => 'This SEC registration number is already registered.',
            'primary_contact_email.email' => 'Please enter a valid primary contact email.',
            'secondary_contact_email.email' => 'Please enter a valid secondary contact email.',
        ];
    }
}
