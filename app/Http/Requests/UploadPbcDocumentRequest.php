<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPbcDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('upload_document');
    }

    public function rules(): array
    {
        $maxSize = config('pbc.file_upload.max_size', 10240); // KB
        $allowedTypes = implode(',', config('pbc.file_upload.allowed_types', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']));
        $maxFiles = config('pbc.file_upload.max_files_per_request', 10);

        return [
            'pbc_request_item_id' => ['required', 'exists:pbc_request_items,id'],
            'files' => ['required', 'array', "max:{$maxFiles}"],
            'files.*' => [
                'required',
                'file',
                "max:{$maxSize}",
                "mimes:{$allowedTypes}",
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'replace_existing' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        $maxSize = config('pbc.file_upload.max_size', 10240);
        $allowedTypes = config('pbc.file_upload.allowed_types', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);
        $maxFiles = config('pbc.file_upload.max_files_per_request', 10);

        return [
            'pbc_request_item_id.required' => 'PBC request item is required.',
            'pbc_request_item_id.exists' => 'Selected PBC request item does not exist.',
            'files.required' => 'Please select at least one file to upload.',
            'files.max' => "You can upload a maximum of {$maxFiles} files at once.",
            'files.*.required' => 'File is required.',
            'files.*.file' => 'Uploaded item must be a valid file.',
            'files.*.max' => "File size cannot exceed " . ($maxSize / 1024) . "MB.",
            'files.*.mimes' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes),
        ];
    }

    protected function prepareForValidation()
    {
        // Convert string 'true'/'false' to boolean
        if ($this->has('replace_existing')) {
            $this->merge([
                'replace_existing' => filter_var($this->replace_existing, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if the PBC request item allows file uploads
            if ($this->pbc_request_item_id) {
                $pbcRequestItem = \App\Models\PbcRequestItem::find($this->pbc_request_item_id);

                if ($pbcRequestItem && !$pbcRequestItem->canUploadFilesBy($this->user())) {
                    $validator->errors()->add('pbc_request_item_id', 'You are not authorized to upload files for this item.');
                }

                // Check if item is already accepted and doesn't allow new uploads
                if ($pbcRequestItem && $pbcRequestItem->status === 'accepted' && !$this->replace_existing) {
                    $validator->errors()->add('files', 'This item has already been accepted. Cannot upload new files unless replacing existing ones.');
                }
            }
        });
    }
}
