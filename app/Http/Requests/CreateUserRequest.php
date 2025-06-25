<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // FIXED: Check if user has permission properly
        return $this->user() && $this->user()->hasPermission('create_user');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'], // Add confirmation
            'entity' => ['nullable', 'string', 'max:255'], // Make entity optional
            'role' => ['required', Rule::in(['system_admin', 'engagement_partner', 'manager', 'associate', 'guest'])],
            'access_level' => ['required', 'integer', 'between:1,5'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required',
            'email.unique' => 'This email address is already taken',
            'password.min' => 'Password must be at least 8 characters',
            'password_confirmation.same' => 'Password confirmation does not match',
            'role.in' => 'Please select a valid role',
            'access_level.between' => 'Access level must be between 1 and 5',
        ];
    }
}

