<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All authenticated users can comment
    }

    public function rules(): array
    {
        return [
            'pbc_request_id' => ['required', 'exists:pbc_requests,id'],
            'comment' => ['required', 'string', 'max:2000'],
            'is_internal' => ['boolean'],
            'parent_id' => ['nullable', 'exists:pbc_comments,id'],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:5120'], // 5MB max per attachment
        ];
    }

    public function messages(): array
    {
        return [
            'pbc_request_id.exists' => 'Selected PBC request does not exist.',
            'comment.required' => 'Comment text is required.',
            'comment.max' => 'Comment cannot exceed 2000 characters.',
            'parent_id.exists' => 'Parent comment does not exist.',
        ];
    }
}
