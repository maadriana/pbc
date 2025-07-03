<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPbcDocumentRequest;
use App\Models\PbcSubmission;
use App\Models\PbcRequestItem;
use App\Services\PbcSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PbcSubmissionController extends BaseController
{
    protected $pbcSubmissionService;

    public function __construct(PbcSubmissionService $pbcSubmissionService)
    {
        $this->pbcSubmissionService = $pbcSubmissionService;
    }

    public function index(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $submissions = $this->pbcSubmissionService->getFilteredSubmissions($request->all());
            return $this->paginated($submissions, 'Documents retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve documents', $e->getMessage(), 500);
        }
    }

    public function store(UploadPbcDocumentRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('upload_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $pbcRequestItem = PbcRequestItem::findOrFail($request->pbc_request_item_id);

            // Check if user can upload files for this item
            if (!$pbcRequestItem->canUploadFilesBy(auth()->user())) {
                return $this->error('Access denied to upload files for this item', null, 403);
            }

            $submissions = $this->pbcSubmissionService->uploadDocuments(
                $pbcRequestItem,
                $request->file('files'),
                $request->validated()
            );

            return $this->success($submissions, 'Documents uploaded successfully', 201);

        } catch (\Exception $e) {
            return $this->error('Failed to upload documents', $e->getMessage(), 500);
        }
    }

    public function show(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDownloadedBy(auth()->user())) {
                return $this->error('Access denied to this document', null, 403);
            }

            $pbcSubmission->load([
                'pbcRequestItem.pbcRequest',
                'uploader',
                'reviewer',
                'comments' => function($query) {
                    $query->where('visibility', 'both')
                          ->orWhere('visibility', auth()->user()->isGuest() ? 'client' : 'internal')
                          ->with('user')
                          ->latest();
                }
            ]);

            return $this->success($pbcSubmission, 'Document retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve document', $e->getMessage(), 500);
        }
    }

    public function destroy(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDeletedBy(auth()->user())) {
                return $this->error('Access denied to delete this document', null, 403);
            }

            $this->pbcSubmissionService->deleteSubmission($pbcSubmission);
            return $this->success(null, 'Document deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete document', $e->getMessage(), 500);
        }
    }

    public function download(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDownloadedBy(auth()->user())) {
                return $this->error('Access denied to download this document', null, 403);
            }

            $filePath = $this->pbcSubmissionService->getDownloadPath($pbcSubmission);

            if (!Storage::disk('pbc-documents')->exists($pbcSubmission->file_path)) {
                return $this->error('File not found', null, 404);
            }

            return Storage::disk('pbc-documents')->download(
                $pbcSubmission->file_path,
                $pbcSubmission->original_filename
            );

        } catch (\Exception $e) {
            return $this->error('Failed to download document', $e->getMessage(), 500);
        }
    }

    public function preview(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDownloadedBy(auth()->user())) {
                return $this->error('Access denied to preview this document', null, 403);
            }

            if (!$pbcSubmission->canBePreviewedInBrowser()) {
                return $this->error('This file type cannot be previewed', null, 422);
            }

            $previewData = $this->pbcSubmissionService->generatePreview($pbcSubmission);
            return $this->success($previewData, 'Preview generated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to generate preview', $e->getMessage(), 500);
        }
    }

    public function approve(PbcSubmission $pbcSubmission, Request $request)
    {
        try {
            if (!$pbcSubmission->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to review this document', null, 403);
            }

            $request->validate([
                'remarks' => 'nullable|string|max:1000',
            ]);

            $approvedSubmission = $this->pbcSubmissionService->approveSubmission(
                $pbcSubmission,
                auth()->id(),
                $request->remarks
            );

            return $this->success($approvedSubmission, 'Document approved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to approve document', $e->getMessage(), 500);
        }
    }

    public function reject(PbcSubmission $pbcSubmission, Request $request)
    {
        try {
            if (!$pbcSubmission->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to review this document', null, 403);
            }

            $request->validate([
                'remarks' => 'required|string|max:1000',
            ]);

            $rejectedSubmission = $this->pbcSubmissionService->rejectSubmission(
                $pbcSubmission,
                auth()->id(),
                $request->remarks
            );

            return $this->success($rejectedSubmission, 'Document rejected');
        } catch (\Exception $e) {
            return $this->error('Failed to reject document', $e->getMessage(), 500);
        }
    }

    public function requestRevision(PbcSubmission $pbcSubmission, Request $request)
    {
        try {
            if (!$pbcSubmission->canBeReviewedBy(auth()->user())) {
                return $this->error('Access denied to review this document', null, 403);
            }

            $request->validate([
                'remarks' => 'required|string|max:1000',
            ]);

            $revisionSubmission = $this->pbcSubmissionService->requestRevision(
                $pbcSubmission,
                auth()->id(),
                $request->remarks
            );

            return $this->success($revisionSubmission, 'Revision requested');
        } catch (\Exception $e) {
            return $this->error('Failed to request revision', $e->getMessage(), 500);
        }
    }

    public function bulkApprove(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('approve_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'exists:pbc_submissions,id',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $result = $this->pbcSubmissionService->bulkApprove(
                $request->submission_ids,
                auth()->id(),
                $request->remarks
            );

            return $this->success($result, 'Bulk approval completed');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk approval', $e->getMessage(), 500);
        }
    }

    public function bulkReject(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('approve_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'exists:pbc_submissions,id',
                'remarks' => 'required|string|max:1000',
            ]);

            $result = $this->pbcSubmissionService->bulkReject(
                $request->submission_ids,
                auth()->id(),
                $request->remarks
            );

            return $this->success($result, 'Bulk rejection completed');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk rejection', $e->getMessage(), 500);
        }
    }

    public function bulkDownload(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'exists:pbc_submissions,id',
            ]);

            $zipFile = $this->pbcSubmissionService->createBulkDownload(
                $request->submission_ids
            );

            return response()->download($zipFile['path'], $zipFile['filename']);
        } catch (\Exception $e) {
            return $this->error('Failed to create bulk download', $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('delete_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'exists:pbc_submissions,id',
            ]);

            $result = $this->pbcSubmissionService->bulkDelete(
                $request->submission_ids
            );

            return $this->success($result, 'Bulk deletion completed');
        } catch (\Exception $e) {
            return $this->error('Failed to perform bulk deletion', $e->getMessage(), 500);
        }
    }

    public function getVersionHistory(PbcRequestItem $pbcRequestItem)
    {
        try {
            if (!$pbcRequestItem->pbcRequest->canBeViewedBy(auth()->user())) {
                return $this->error('Access denied to this PBC item', null, 403);
            }

            $versionHistory = $this->pbcSubmissionService->getVersionHistory($pbcRequestItem);
            return $this->success($versionHistory, 'Version history retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve version history', $e->getMessage(), 500);
        }
    }

    public function createNewVersion(PbcSubmission $pbcSubmission, UploadPbcDocumentRequest $request)
    {
        try {
            if (!$pbcSubmission->pbcRequestItem->canUploadFilesBy(auth()->user())) {
                return $this->error('Access denied to upload new version', null, 403);
            }

            $newVersion = $this->pbcSubmissionService->createNewVersion(
                $pbcSubmission,
                $request->file('file'),
                $request->validated()
            );

            return $this->success($newVersion, 'New version uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to upload new version', $e->getMessage(), 500);
        }
    }

    public function getStats()
    {
        try {
            if (!auth()->user()->hasPermission('view_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $stats = $this->pbcSubmissionService->getDocumentStats();
            return $this->success($stats, 'Document statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve document statistics', $e->getMessage(), 500);
        }
    }

    public function getDuplicates(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view_document')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $duplicates = $this->pbcSubmissionService->findDuplicateFiles($request->all());
            return $this->paginated($duplicates, 'Duplicate files retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to find duplicate files', $e->getMessage(), 500);
        }
    }

    public function archive(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDeletedBy(auth()->user())) {
                return $this->error('Access denied to archive this document', null, 403);
            }

            $archivedSubmission = $this->pbcSubmissionService->archiveSubmission($pbcSubmission);
            return $this->success($archivedSubmission, 'Document archived successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to archive document', $e->getMessage(), 500);
        }
    }

    public function restore(PbcSubmission $pbcSubmission)
    {
        try {
            if (!$pbcSubmission->canBeDeletedBy(auth()->user())) {
                return $this->error('Access denied to restore this document', null, 403);
            }

            $restoredSubmission = $this->pbcSubmissionService->restoreSubmission($pbcSubmission);
            return $this->success($restoredSubmission, 'Document restored successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to restore document', $e->getMessage(), 500);
        }
    }
}
