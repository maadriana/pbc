<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\PbcDocument;
use App\Models\PbcRequest;
use App\Services\PbcDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PbcDocumentController extends BaseController
{
    protected $pbcDocumentService;

    public function __construct(PbcDocumentService $pbcDocumentService)
    {
        $this->pbcDocumentService = $pbcDocumentService;
    }

    public function index(Request $request)
    {
        try {
            $documents = $this->pbcDocumentService->getFilteredDocuments($request->all(), $request->user());
            return $this->paginated($documents, 'Documents retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve documents', $e->getMessage(), 500);
        }
    }

    public function store(UploadDocumentRequest $request)
    {
        try {
            $this->authorize('upload_document');

            $documents = $this->pbcDocumentService->uploadDocuments($request->validated(), $request->user());
            return $this->success($documents, 'Documents uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to upload documents', $e->getMessage(), 500);
        }
    }

    public function show(PbcDocument $document)
    {
        try {
            $this->authorize('download_document');

            $document->load(['pbcRequest', 'uploadedBy', 'reviewedBy']);
            return $this->success($document, 'Document retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve document', $e->getMessage(), 500);
        }
    }

    public function download(PbcDocument $document)
    {
        try {
            $this->authorize('download_document');

            if (!Storage::exists($document->file_path)) {
                return $this->error('File not found', null, 404);
            }

            return Storage::download($document->file_path, $document->original_name);
        } catch (\Exception $e) {
            return $this->error('Failed to download document', $e->getMessage(), 500);
        }
    }

    public function approve(PbcDocument $document, Request $request)
    {
        try {
            $this->authorize('approve_document');

            $request->validate([
                'comments' => 'nullable|string|max:1000'
            ]);

            $this->pbcDocumentService->approveDocument($document, $request->user(), $request->comments);
            return $this->success(null, 'Document approved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to approve document', $e->getMessage(), 500);
        }
    }

    public function reject(PbcDocument $document, Request $request)
    {
        try {
            $this->authorize('reject_document');

            $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            $this->pbcDocumentService->rejectDocument($document, $request->user(), $request->reason);
            return $this->success(null, 'Document rejected successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to reject document', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcDocument $document)
    {
        try {
            $this->authorize('delete_document');

            $this->pbcDocumentService->deleteDocument($document);
            return $this->success(null, 'Document deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete document', $e->getMessage(), 500);
        }
    }

    public function preview(PbcDocument $document)
    {
        try {
            $this->authorize('download_document');

            if (!Storage::exists($document->file_path)) {
                return $this->error('File not found', null, 404);
            }

            return response()->file(Storage::path($document->file_path));
        } catch (\Exception $e) {
            return $this->error('Failed to preview document', $e->getMessage(), 500);
        }
    }
}
