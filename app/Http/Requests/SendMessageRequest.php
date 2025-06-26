<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasPermission('send_messages');
    }

    public function rules()
    {
        return [
            'conversation_id' => 'required|exists:pbc_conversations,id',
            'message' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
            'reply_to_id' => 'nullable|exists:pbc_messages,id'
        ];
    }

    public function messages()
    {
        return [
            'conversation_id.required' => 'Conversation ID is required',
            'conversation_id.exists' => 'Invalid conversation',
            'message.max' => 'Message cannot exceed 5000 characters',
            'attachments.max' => 'Maximum 5 attachments allowed',
            'attachments.*.max' => 'Each file must be less than 10MB',
            'attachments.*.mimes' => 'Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty($this->message) && empty($this->file('attachments'))) {
                $validator->errors()->add('message', 'Either message or attachments are required');
            }
        });
    }
}
