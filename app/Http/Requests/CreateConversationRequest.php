<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasPermission('create_conversations') ||
               auth()->user()->hasPermission('send_messages');
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id|distinct',
            'title' => 'nullable|string|max:255'
        ];
    }

    public function messages()
    {
        return [
            'client_id.required' => 'Client is required',
            'client_id.exists' => 'Invalid client',
            'project_id.required' => 'Project is required',
            'project_id.exists' => 'Invalid project',
            'participant_ids.required' => 'At least one participant is required',
            'participant_ids.min' => 'At least one participant is required',
            'participant_ids.*.exists' => 'One or more participants are invalid',
            'participant_ids.*.distinct' => 'Duplicate participants are not allowed'
        ];
    }
}
