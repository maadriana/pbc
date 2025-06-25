<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('edit_user');
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'password_confirmation' => ['sometimes', 'nullable', 'same:password'],
            'entity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'required', Rule::in(['system_admin', 'engagement_partner', 'manager', 'associate', 'guest'])],
            'access_level' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'contact_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string 'true'/'false' to boolean for is_active
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            ]);
        }

        // Convert access_level to integer if present
        if ($this->has('access_level')) {
            $this->merge([
                'access_level' => (int) $this->access_level
            ]);
        }

        // Remove empty password fields to prevent validation issues
        if ($this->has('password') && empty($this->password)) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.min' => 'Password must be at least 8 characters.',
            'password_confirmation.same' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role.',
            'role.in' => 'Invalid role selected.',
            'access_level.required' => 'Please select an access level.',
            'access_level.between' => 'Access level must be between 1 and 5.',
        ];
    }
}
