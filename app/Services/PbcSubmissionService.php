<?php

namespace App\Services;

use App\Models\PbcSubmission;
use App\Models\PbcRequestItem;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class PbcSubmissionService
{
    public function getFilteredSubmissions(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = PbcSubmission::with([
            'pbcRequestItem.pbcRequest.project.client',
            'uploader',
            'reviewer'
        ]);

        // Apply access control
        if ($user->isGuest()) {
            $query->whereHas('pbcRequestItem.pbcRequest', function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('project', function($projectQuery) use ($user) {
                      $projectQuery->where('contact_email', $user->email);
                  });
            });
        } elseif (!$user->isSystemAdmin()) {
            $query->whereHas('pbcRequestItem.pbcRequest.project', function($projectQuery) use ($user) {
                $projectQuery->where(function($q) use ($user) {
                    $q->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
                });
            });
        }

        // Apply filters
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('review_remarks', 'like', "%{$search}%");
            });
        })
        ->when($filters['pbc_request_item_id'] ?? null, function ($query, $itemId) {
            $query->where('pbc_request_item_id', $itemId);
        })
        ->when($filters['pbc_request_id'] ?? null, function ($query, $requestId) {
            $query->where('pbc_request_id', $requestId);
        })
        ->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($filters['uploaded_by'] ?? null, function ($query, $uploadedBy) {
            $query->where('uploaded_by', $uploadedBy);
        })
        ->when($filters['reviewed_by'] ?? null, function ($query, $reviewedBy) {
            $query->where('reviewed_by', $reviewedBy);
        })
        ->when($filters['is_active'] ?? null, function ($query, $isActive) {
            $query->where('is_active', $isActive);
        })
        ->when($filters['file_type'] ?? null, function ($query, $fileType) {
            $query->where('mime_type', 'like', "%{$fileType}%");
        })
        ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            $query->where('uploaded_at', '>=', $dateFrom);
        })
        ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            $query->where('uploaded_at', '<=', $dateTo);
        });

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'uploaded_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function uploadDocuments(PbcRequestItem $pbcRequestItem, $files, array $metadata = []): array
    {
        DB::beginTransaction();

        try {
            $uploadedSubmissions = [];
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                $submission = $this->uploadSingleDocument($pbcRequestItem, $file, $metadata);
                $uploadedSubmissions[] = $submission;
            }

            // Update item status to submitted if it was pending
            if ($pbcRequestItem->status === 'pending') {
                $pbcRequestItem->update([
                    'status' => 'submitted',
                    'date_submitted' => now(),
                ]);
            }

            // Update parent request progress
            $pbcRequestItem->pbcRequest->updateProgress();

            DB::commit();
            return $uploadedSubmissions;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function uploadSingleDocument(PbcRequestItem $pbcRequestItem, UploadedFile $file, array $metadata = []): PbcSubmission
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $this->generateFilePath($pbcRequestItem, $storedFilename);

        // Store file
        $file->storeAs(
            dirname($filePath),
            basename($filePath),
            'pbc-documents'
        );

        // Calculate file hash for duplicate detection
        $fileHash = hash_file('sha256', $file->getPathname());

        // Determine version number
        $version = $pbcRequestItem->submissions()->max('version') + 1;

        // Create submission record
        $submission = PbcSubmission::create([
            'pbc_request_item_id' => $pbcRequestItem->id,
            'pbc_request_id' => $pbcRequestItem->pbc_request_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => $fileHash,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'status' => 'pending',
            'version' => $version,
            'metadata' => $metadata,
            'is_active' => true,
        ]);

        // Log activity
        AuditLog::logDocumentActivity('uploaded', $submission,
            "File '{$submission->original_filename}' uploaded for PBC item '{$pbcRequestItem->getDisplayName()}'",
            auth()->user());

        return $submission;
    }

    public function deleteSubmission(PbcSubmission $submission): bool
    {
        DB::beginTransaction();

        try {
            // Log activity before deletion
            AuditLog::logDocumentActivity('deleted', $submission,
                "File '{$submission->original_filename}' deleted", auth()->user());

            // Delete physical file
            Storage::disk('pbc-documents')->delete($submission->file_path);

            // Soft delete the record
            $result = $submission->delete();

            // Update parent item progress
            $submission->pbcRequestItem->pbcRequest->updateProgress();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function approveSubmission(PbcSubmission $submission, int $reviewedBy, ?string $remarks = null): PbcSubmission
    {
        DB::beginTransaction();

        try {
            $submission->approve($reviewedBy, $remarks);

            DB::commit();
            return $submission->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function rejectSubmission(PbcSubmission $submission, int $reviewedBy, string $remarks): PbcSubmission
    {
        DB::beginTransaction();

        try {
            $submission->reject($reviewedBy, $remarks);

            DB::commit();
            return $submission->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function requestRevision(PbcSubmission $submission, int $reviewedBy, string $remarks): PbcSubmission
    {
        DB::beginTransaction();

        try {
            $submission->requestRevision($reviewedBy, $remarks);

            DB::commit();
            return $submission->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkApprove(array $submissionIds, int $reviewedBy, ?string $remarks = null): array
    {
        DB::beginTransaction();

        try {
            $submissions = PbcSubmission::whereIn('id', $submissionIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($submissions as $submission) {
                try {
                    if (!$submission->canBeReviewedBy(auth()->user())) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for file: {$submission->original_filename}";
                        continue;
                    }

                    $this->approveSubmission($submission, $reviewedBy, $remarks);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to approve '{$submission->original_filename}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkReject(array $submissionIds, int $reviewedBy, string $remarks): array
    {
        DB::beginTransaction();

        try {
            $submissions = PbcSubmission::whereIn('id', $submissionIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($submissions as $submission) {
                try {
                    if (!$submission->canBeReviewedBy(auth()->user())) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for file: {$submission->original_filename}";
                        continue;
                    }

                    $this->rejectSubmission($submission, $reviewedBy, $remarks);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to reject '{$submission->original_filename}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function bulkDelete(array $submissionIds): array
    {
        DB::beginTransaction();

        try {
            $submissions = PbcSubmission::whereIn('id', $submissionIds)->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($submissions as $submission) {
                try {
                    if (!$submission->canBeDeletedBy(auth()->user())) {
                        $results['failed']++;
                        $results['errors'][] = "Access denied for file: {$submission->original_filename}";
                        continue;
                    }

                    $this->deleteSubmission($submission);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to delete '{$submission->original_filename}': " . $e->getMessage();
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createBulkDownload(array $submissionIds): array
    {
        $submissions = PbcSubmission::whereIn('id', $submissionIds)->get();

        // Filter submissions user can download
        $allowedSubmissions = $submissions->filter(function($submission) {
            return $submission->canBeDownloadedBy(auth()->user());
        });

        if ($allowedSubmissions->isEmpty()) {
            throw new \Exception('No files available for download');
        }

        // Create temporary zip file
        $zipFilename = 'pbc_documents_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create zip file');
        }

        foreach ($allowedSubmissions as $submission) {
            $filePath = Storage::disk('pbc-documents')->path($submission->file_path);
            if (file_exists($filePath)) {
                // Create a unique name to avoid conflicts
                $zipEntryName = $submission->pbcRequestItem->getDisplayName() . '_' . $submission->original_filename;
                $zip->addFile($filePath, $zipEntryName);
            }
        }

        $zip->close();

        return [
            'path' => $zipPath,
            'filename' => $zipFilename,
            'count' => $allowedSubmissions->count(),
        ];
    }

    public function getVersionHistory(PbcRequestItem $pbcRequestItem): array
    {
        $submissions = $pbcRequestItem->submissions()
            ->with(['uploader', 'reviewer'])
            ->orderBy('version', 'desc')
            ->get();

        return $submissions->map(function($submission) {
            return [
                'id' => $submission->id,
                'version' => $submission->version,
                'filename' => $submission->original_filename,
                'file_size' => $submission->getFileSizeFormatted(),
                'status' => $submission->status,
                'uploaded_by' => $submission->uploader->name,
                'uploaded_at' => $submission->getUploadedAtFormatted(),
                'reviewed_by' => $submission->reviewer?->name,
                'reviewed_at' => $submission->getReviewedAtFormatted(),
                'remarks' => $submission->review_remarks,
                'is_active' => $submission->is_active,
                'is_latest' => $submission->isLatestVersion(),
            ];
        })->toArray();
    }

    public function createNewVersion(PbcSubmission $originalSubmission, UploadedFile $file, array $metadata = []): PbcSubmission
    {
        DB::beginTransaction();

        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $this->generateFilePath($originalSubmission->pbcRequestItem, $storedFilename);

            // Store file
            $file->storeAs(
                dirname($filePath),
                basename($filePath),
                'pbc-documents'
            );

            // Calculate file hash
            $fileHash = hash_file('sha256', $file->getPathname());

            // Create new version
            $newSubmission = $originalSubmission->createNewVersion([
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'metadata' => $metadata,
            ], auth()->id());

            DB::commit();
            return $newSubmission;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function generatePreview(PbcSubmission $submission): array
    {
        if (!$submission->canBePreviewedInBrowser()) {
            throw new \Exception('File cannot be previewed');
        }

        $previewData = [
            'type' => $submission->isImage() ? 'image' : 'pdf',
            'url' => route('pbc-submissions.download', ['pbcSubmission' => $submission->id]),
            'filename' => $submission->original_filename,
            'file_size' => $submission->getFileSizeFormatted(),
        ];

        if ($submission->isImage()) {
            // For images, we can provide direct URL
            $previewData['direct_url'] = Storage::disk('pbc-documents')->url($submission->file_path);
        }

        return $previewData;
    }

    public function getDownloadPath(PbcSubmission $submission): string
    {
        return Storage::disk('pbc-documents')->path($submission->file_path);
    }

    public function archiveSubmission(PbcSubmission $submission): PbcSubmission
    {
        $submission->archive();

        AuditLog::logDocumentActivity('archived', $submission,
            "File '{$submission->original_filename}' archived", auth()->user());

        return $submission;
    }

    public function restoreSubmission(PbcSubmission $submission): PbcSubmission
    {
        $submission->restore();

        AuditLog::logDocumentActivity('restored', $submission,
            "File '{$submission->original_filename}' restored", auth()->user());

        return $submission;
    }

    public function getDocumentStats(): array
    {
        $user = auth()->user();

        $query = PbcSubmission::query();

        // Apply access control
        if ($user->isGuest()) {
            $query->whereHas('pbcRequestItem.pbcRequest', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        } elseif (!$user->isSystemAdmin()) {
            $query->whereHas('pbcRequestItem.pbcRequest.project', function($projectQuery) use ($user) {
                $projectQuery->where(function($q) use ($user) {
                    $q->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
                });
            });
        }

        $stats = [
            'total_documents' => $query->count(),
            'total_size' => $query->sum('file_size'),
            'by_status' => [
                'pending' => $query->where('status', 'pending')->count(),
                'under_review' => $query->where('status', 'under_review')->count(),
                'accepted' => $query->where('status', 'accepted')->count(),
                'rejected' => $query->where('status', 'rejected')->count(),
            ],
            'by_file_type' => $query->selectRaw('mime_type, COUNT(*) as count')
                                   ->groupBy('mime_type')
                                   ->pluck('count', 'mime_type')
                                   ->toArray(),
            'recent_uploads' => $query->where('uploaded_at', '>=', now()->subDays(7))->count(),
            'average_file_size' => $query->avg('file_size'),
        ];

        // Format total size
        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);
        $stats['average_file_size_formatted'] = $this->formatBytes($stats['average_file_size']);

        return $stats;
    }

    public function findDuplicateFiles(array $filters = []): LengthAwarePaginator
    {
        $query = PbcSubmission::selectRaw('file_hash, COUNT(*) as duplicate_count')
            ->havingRaw('COUNT(*) > 1')
            ->groupBy('file_hash');

        $duplicateHashes = $query->pluck('file_hash');

        return PbcSubmission::whereIn('file_hash', $duplicateHashes)
            ->with(['pbcRequestItem.pbcRequest', 'uploader'])
            ->orderBy('file_hash')
            ->orderBy('uploaded_at')
            ->paginate($filters['per_page'] ?? 25);
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxSize = config('pbc.file_upload.max_size', 10240) * 1024; // Convert KB to bytes
        $allowedTypes = config('pbc.file_upload.allowed_types', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
        }
    }

    private function generateFilePath(PbcRequestItem $pbcRequestItem, string $filename): string
    {
        $projectId = $pbcRequestItem->pbcRequest->project_id;
        $requestId = $pbcRequestItem->pbc_request_id;
        $itemId = $pbcRequestItem->id;

        return "projects/{$projectId}/requests/{$requestId}/items/{$itemId}/{$filename}";
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
