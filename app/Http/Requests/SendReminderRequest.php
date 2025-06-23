<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('send_reminder');
    }

    public function rules(): array
    {
        return [
            'pbc_request_id' => ['required', 'exists:pbc_requests,id'],
            'sent_to' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['initial', 'follow_up', 'urgent', 'final_notice'])],
        ];
    }

    public function messages(): array
    {
        return [
            'pbc_request_id.exists' => 'Selected PBC request does not exist.',
            'sent_to.exists' => 'Selected recipient does not exist.',
            'subject.required' => 'Subject is required.',
            'message.required' => 'Message is required.',
            'type.in' => 'Invalid reminder type.',
        ];
    }
}
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }
}
