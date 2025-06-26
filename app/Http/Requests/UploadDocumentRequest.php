<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('upload_document');
    }

    public function rules(): array
    {
        return [
            'pbc_request_id' => ['required', 'exists:pbc_requests,id'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'max:10240', // 10MB max per file
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,txt,csv,zip,rar'
            ],
            'comments' => ['nullable', 'string', 'max:1000'],
            'version' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'pbc_request_id.required' => 'Please select a PBC request.',
            'pbc_request_id.exists' => 'Selected PBC request does not exist.',
            'files.required' => 'Please select at least one file to upload.',
            'files.max' => 'You can upload maximum 10 files at once.',
            'files.*.required' => 'All files are required.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.max' => 'Each file must not exceed 10MB.',
            'files.*.mimes' => 'File type not supported. Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, GIF, TXT, CSV, ZIP, RAR.',
            'comments.max' => 'Comments cannot exceed 1000 characters.',
            'version.max' => 'Version cannot exceed 10 characters.',
        ];
    }

    protected function prepareForValidation()
    {
        // Set default version if not provided
        if (!$this->version) {
            $this->merge(['version' => '1.0']);
        }
    }
}
