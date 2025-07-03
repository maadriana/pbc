<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('view_pbc_request');
    }

    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', 'in:App\Models\PbcRequest,App\Models\PbcRequestItem,App\Models\PbcSubmission'],
            'commentable_id' => ['required', 'integer'],
            'comment' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', 'string', 'in:general,question,clarification,issue,reminder'],
            'visibility' => ['sometimes', 'string', 'in:internal,client,both'],
            'parent_id' => ['nullable', 'exists:pbc_comments,id'],
        ];
    }

    protected function prepareForValidation()
    {
        // Set defaults
        if (!$this->has('type')) {
            $this->merge(['type' => 'general']);
        }

        if (!$this->has('visibility')) {
            $visibility = $this->user()->isGuest() ? 'client' : 'both';
            $this->merge(['visibility' => $visibility]);
        }
    }
}

// SendReminderRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('send_reminder');
    }

    public function rules(): array
    {
        return [
            'remindable_type' => ['required', 'string', 'in:App\Models\PbcRequest,App\Models\PbcRequestItem'],
            'remindable_id' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', 'string', 'in:initial,follow_up,urgent,final_notice'],
            'method' => ['sometimes', 'string', 'in:email,sms,system'],
            'sent_to' => ['required', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    protected function prepareForValidation()
    {
        // Set defaults
        if (!$this->has('type')) {
            $this->merge(['type' => 'follow_up']);
        }

        if (!$this->has('method')) {
            $this->merge(['method' => 'email']);
        }

        if (!$this->has('scheduled_at')) {
            $this->merge(['scheduled_at' => now()]);
        }
    }
}
