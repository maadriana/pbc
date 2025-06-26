<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\PbcDocument;
use App\Models\PbcRequest;
use App\Services\PbcDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PbcDocumentController extends BaseController
{
    protected $pbcDocumentService;

    public function __construct(PbcDocumentService $pbcDocumentService)
    {
        $this->pbcDocumentService = $pbcDocumentService;
    }

    /**
     * Display a listing of documents with filters
     */
    public function index(Request $request)
    {
        try {
            $documents = $this->pbcDocumentService->getFilteredDocuments($request->all(), $request->user());
            return $this->paginated($documents, 'Documents retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve documents', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'filters' => $request->all()
            ]);
            return $this->error('Failed to retrieve documents', $e->getMessage(), 500);
        }
    }

    /**
     * Store newly uploaded documents
     */
    public function store(UploadDocumentRequest $request)
    {
        try {
            Gate::authorize('upload_document');

            $documents = $this->pbcDocumentService->uploadDocuments($request->validated(), $request->user());

            Log::info('Documents uploaded successfully', [
                'user_id' => $request->user()->id,
                'pbc_request_id' => $request->pbc_request_id,
                'file_count' => count($documents)
            ]);

            return $this->success($documents, 'Documents uploaded successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to upload documents', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'pbc_request_id' => $request->pbc_request_id ?? null
            ]);
            return $this->error('Failed to upload documents', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified document
     */
    public function show(PbcDocument $document)
    {
        try {
            Gate::authorize('download_document');

            // Update last accessed timestamp
            $document->update(['last_accessed_at' => now()]);

            $document->load(['pbcRequest.project.client', 'uploadedBy', 'reviewedBy']);
            return $this->success($document, 'Document retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to retrieve document', $e->getMessage(), 500);
        }
    }

    /**
     * Download the specified document
     */
    public function download(PbcDocument $document)
    {
        try {
            Gate::authorize('download_document');

            // Update last accessed timestamp
            $document->update(['last_accessed_at' => now()]);

            // Try cloud download first, fallback to local
            $downloadUrl = $this->pbcDocumentService->getDownloadUrl($document);

            if (filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
                // Cloud download - redirect to cloud URL
                return redirect($downloadUrl);
            } else {
                // Local download
                if (!Storage::exists($document->file_path)) {
                    return $this->error('File not found', null, 404);
                }

                Log::info('Document downloaded', [
                    'document_id' => $document->id,
                    'user_id' => auth()->id(),
                    'filename' => $document->original_name
                ]);

                return Storage::download($document->file_path, $document->original_name);
            }
        } catch (\Exception $e) {
            Log::error('Failed to download document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to download document', $e->getMessage(), 500);
        }
    }

    /**
     * Approve the specified document
     */
    public function approve(PbcDocument $document, Request $request)
    {
        try {
            Gate::authorize('approve_document');

            $request->validate([
                'comments' => 'nullable|string|max:1000'
            ]);

            $this->pbcDocumentService->approveDocument($document, $request->user(), $request->comments);

            Log::info('Document approved', [
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
                'comments' => $request->comments
            ]);

            return $this->success(null, 'Document approved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to approve document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to approve document', $e->getMessage(), 500);
        }
    }

    /**
     * Reject the specified document
     */
    public function reject(PbcDocument $document, Request $request)
    {
        try {
            Gate::authorize('reject_document');

            $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            $this->pbcDocumentService->rejectDocument($document, $request->user(), $request->reason);

            Log::info('Document rejected', [
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
                'reason' => $request->reason
            ]);

            return $this->success(null, 'Document rejected successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reject document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to reject document', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified document
     */
    public function destroy(PbcDocument $document)
    {
        try {
            Gate::authorize('delete_document');

            $documentName = $document->original_name;
            $documentId = $document->id;

            $this->pbcDocumentService->deleteDocument($document);

            Log::info('Document deleted', [
                'document_id' => $documentId,
                'filename' => $documentName,
                'user_id' => auth()->id()
            ]);

            return $this->success(null, 'Document deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to delete document', $e->getMessage(), 500);
        }
    }

    /**
     * Preview the specified document
     */
    public function preview(PbcDocument $document)
    {
        try {
            Gate::authorize('download_document');

            // Update last accessed timestamp
            $document->update(['last_accessed_at' => now()]);

            if (!empty($document->cloud_url)) {
                // Use cloud URL for preview
                return redirect($document->cloud_url);
            } else {
                // Use local file for preview
                if (!Storage::exists($document->file_path)) {
                    return $this->error('File not found', null, 404);
                }

                return response()->file(Storage::path($document->file_path));
            }
        } catch (\Exception $e) {
            Log::error('Failed to preview document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to preview document', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk approve multiple documents
     */
    public function bulkApprove(Request $request)
    {
        try {
            Gate::authorize('approve_document');

            $request->validate([
                'document_ids' => 'required|array|min:1|max:100',
                'document_ids.*' => 'exists:pbc_documents,id',
                'comments' => 'nullable|string|max:1000'
            ]);

            $documents = PbcDocument::whereIn('id', $request->document_ids)->get();
            $approvedCount = 0;
            $skippedCount = 0;

            foreach ($documents as $document) {
                if ($document->status === 'pending') {
                    $this->pbcDocumentService->approveDocument($document, $request->user(), $request->comments);
                    $approvedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('Bulk approve completed', [
                'approved_count' => $approvedCount,
                'skipped_count' => $skippedCount,
                'user_id' => $request->user()->id,
                'total_requested' => count($request->document_ids)
            ]);

            $message = "{$approvedCount} documents approved successfully";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped - not pending)";
            }

            return $this->success([
                'approved_count' => $approvedCount,
                'skipped_count' => $skippedCount,
                'total_count' => count($request->document_ids)
            ], $message);
        } catch (\Exception $e) {
            Log::error('Failed to bulk approve documents', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_ids' => $request->document_ids ?? []
            ]);
            return $this->error('Failed to approve documents', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk reject multiple documents
     */
    public function bulkReject(Request $request)
    {
        try {
            Gate::authorize('reject_document');

            $request->validate([
                'document_ids' => 'required|array|min:1|max:100',
                'document_ids.*' => 'exists:pbc_documents,id',
                'reason' => 'required|string|max:1000'
            ]);

            $documents = PbcDocument::whereIn('id', $request->document_ids)->get();
            $rejectedCount = 0;
            $skippedCount = 0;

            foreach ($documents as $document) {
                if ($document->status === 'pending') {
                    $this->pbcDocumentService->rejectDocument($document, $request->user(), $request->reason);
                    $rejectedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('Bulk reject completed', [
                'rejected_count' => $rejectedCount,
                'skipped_count' => $skippedCount,
                'user_id' => $request->user()->id,
                'reason' => $request->reason,
                'total_requested' => count($request->document_ids)
            ]);

            $message = "{$rejectedCount} documents rejected successfully";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped - not pending)";
            }

            return $this->success([
                'rejected_count' => $rejectedCount,
                'skipped_count' => $skippedCount,
                'total_count' => count($request->document_ids)
            ], $message);
        } catch (\Exception $e) {
            Log::error('Failed to bulk reject documents', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_ids' => $request->document_ids ?? []
            ]);
            return $this->error('Failed to reject documents', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk download multiple documents as ZIP
     */
    public function bulkDownload(Request $request)
    {
        try {
            Gate::authorize('download_document');

            $request->validate([
                'document_ids' => 'required|array|min:1|max:50',
                'document_ids.*' => 'exists:pbc_documents,id'
            ]);

            $documents = PbcDocument::whereIn('id', $request->document_ids)->get();

            if ($documents->isEmpty()) {
                return $this->error('No documents found', null, 404);
            }

            // Create temporary ZIP file
            $zipFileName = 'pbc_documents_' . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                Log::error('Could not create ZIP file', [
                    'zip_path' => $zipPath,
                    'user_id' => auth()->id()
                ]);
                return $this->error('Could not create ZIP file', null, 500);
            }

            $addedFiles = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    $filePath = Storage::path($document->file_path);

                    if (file_exists($filePath)) {
                        // Use original name, but handle duplicates
                        $fileName = $document->original_name;
                        $counter = 1;

                        while ($zip->locateName($fileName) !== false) {
                            $pathInfo = pathinfo($document->original_name);
                            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                            $fileName = $pathInfo['filename'] . "_{$counter}" . $extension;
                            $counter++;
                        }

                        $zip->addFile($filePath, $fileName);
                        $addedFiles++;
                    } else {
                        $errors[] = "File not found: {$document->original_name}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error adding {$document->original_name}: " . $e->getMessage();
                }
            }

            $zip->close();

            if ($addedFiles === 0) {
                unlink($zipPath);
                Log::warning('No files were available for bulk download', [
                    'document_ids' => $request->document_ids,
                    'errors' => $errors,
                    'user_id' => auth()->id()
                ]);
                return $this->error('No files were available for download', null, 404);
            }

            Log::info('Bulk download completed', [
                'files_added' => $addedFiles,
                'total_requested' => count($documents),
                'zip_file' => $zipFileName,
                'user_id' => auth()->id(),
                'errors' => $errors
            ]);

            // Return the ZIP file
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to create bulk download', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_ids' => $request->document_ids ?? []
            ]);
            return $this->error('Failed to create download archive', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk delete multiple documents
     */
    public function bulkDelete(Request $request)
    {
        try {
            Gate::authorize('delete_document');

            $request->validate([
                'document_ids' => 'required|array|min:1|max:100',
                'document_ids.*' => 'exists:pbc_documents,id'
            ]);

            $documents = PbcDocument::whereIn('id', $request->document_ids)->get();
            $deletedCount = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    $this->pbcDocumentService->deleteDocument($document);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete {$document->original_name}: " . $e->getMessage();
                }
            }

            Log::info('Bulk delete completed', [
                'deleted_count' => $deletedCount,
                'total_requested' => count($documents),
                'errors' => $errors,
                'user_id' => auth()->id()
            ]);

            $message = "{$deletedCount} documents deleted successfully";
            if (!empty($errors)) {
                $message .= " (" . count($errors) . " errors occurred)";
            }

            return $this->success([
                'deleted_count' => $deletedCount,
                'error_count' => count($errors),
                'total_count' => count($documents),
                'errors' => $errors
            ], $message);
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete documents', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_ids' => $request->document_ids ?? []
            ]);
            return $this->error('Failed to delete documents', $e->getMessage(), 500);
        }
    }

    /**
     * Get storage and document statistics
     */
    public function getStats(Request $request)
    {
        try {
            $stats = $this->pbcDocumentService->getStorageStats($request->user());

            // Add additional statistics
            $additionalStats = [
                'recent_uploads_count' => PbcDocument::where('created_at', '>=', now()->subDays(7))->count(),
                'pending_approval_count' => PbcDocument::where('status', 'pending')->count(),
                'files_by_type' => PbcDocument::selectRaw('file_type, COUNT(*) as count')
                    ->groupBy('file_type')
                    ->pluck('count', 'file_type')
                    ->toArray(),
                'files_by_status' => PbcDocument::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'upload_trend' => PbcDocument::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date')
                    ->map(function ($item) {
                        return $item->count;
                    })
                    ->toArray()
            ];

            $combinedStats = array_merge($stats, $additionalStats);

            return $this->success($combinedStats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return $this->error('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Clean up old temporary files (can be called via scheduled job)
     */
    public function cleanupTempFiles()
    {
        try {
            $tempPath = storage_path('app/temp');

            if (!file_exists($tempPath)) {
                return;
            }

            $files = glob($tempPath . '/*');
            $deletedCount = 0;

            foreach ($files as $file) {
                if (is_file($file) && time() - filemtime($file) > 3600) { // 1 hour old
                    unlink($file);
                    $deletedCount++;
                }
            }

            Log::info('Temp files cleanup completed', [
                'deleted_files' => $deletedCount
            ]);

            return $this->success(['deleted_files' => $deletedCount], 'Cleanup completed');
        } catch (\Exception $e) {
            Log::error('Failed to cleanup temp files', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Failed to cleanup temp files', $e->getMessage(), 500);
        }
    }
    public function uploadCenterPage()
{
    return view('pbc-documents.index');
}
}
