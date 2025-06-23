<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_user');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'entity' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['system_admin', 'engagement_partner', 'manager', 'associate', 'guest'])],
            'access_level' => ['required', 'integer', 'between:1,5'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
