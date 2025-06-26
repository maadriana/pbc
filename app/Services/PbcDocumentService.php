<?php

namespace App\Services;

use App\Models\PbcDocument;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PbcDocumentService
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService = null)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function getFilteredDocuments(array $filters, User $user): LengthAwarePaginator
    {
        $query = PbcDocument::with(['pbcRequest.project.client', 'uploadedBy', 'reviewedBy'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('original_name', 'like', "%{$search}%")
                      ->orWhere('file_type', 'like', "%{$search}%")
                      ->orWhereHas('pbcRequest', function ($rq) use ($search) {
                          $rq->where('title', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['pbc_request_id'] ?? null, function ($query, $pbcRequestId) {
                $query->where('pbc_request_id', $pbcRequestId);
            })
            ->when($filters['file_type'] ?? null, function ($query, $fileType) {
                $query->where('file_type', $fileType);
            })
            ->when($filters['uploaded_by'] ?? null, function ($query, $uploadedBy) {
                $query->where('uploaded_by', $uploadedBy);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        // Apply user-based filtering
        if ($user->isGuest()) {
            $query->whereHas('pbcRequest', function ($q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            if (!empty($projectIds)) {
                $query->whereHas('pbcRequest', function ($q) use ($projectIds) {
                    $q->whereIn('project_id', $projectIds);
                });
            }
        }

        $query->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function uploadDocuments(array $data, User $user): array
    {
        $uploadedDocuments = [];
        $pbcRequestId = $data['pbc_request_id'];
        $comments = $data['comments'] ?? null;
        $version = $data['version'] ?? '1.0';

        foreach ($data['files'] as $file) {
            try {
                $document = $this->uploadSingleDocument($file, $pbcRequestId, $user, $comments, $version);
                $uploadedDocuments[] = $document;
            } catch (\Exception $e) {
                // Log error but continue with other files
                \Log::error('Failed to upload file: ' . $file->getClientOriginalName(), [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'pbc_request_id' => $pbcRequestId
                ]);

                // Add error info to response
                $uploadedDocuments[] = [
                    'error' => true,
                    'filename' => $file->getClientOriginalName(),
                    'message' => 'Failed to upload: ' . $e->getMessage()
                ];
            }
        }

        return $uploadedDocuments;
    }

    public function approveDocument(PbcDocument $document, User $user, ?string $comments = null): PbcDocument
    {
        $document->approve($user->id, $comments);

        try {
            $this->logActivity('document_approved', $document, $user, 'Document approved');
        } catch (\Exception $e) {
            \Log::warning('Could not log document approval: ' . $e->getMessage());
        }

        return $document;
    }

    public function rejectDocument(PbcDocument $document, User $user, string $reason): PbcDocument
    {
        $document->reject($user->id, $reason);

        try {
            $this->logActivity('document_rejected', $document, $user, 'Document rejected');
        } catch (\Exception $e) {
            \Log::warning('Could not log document rejection: ' . $e->getMessage());
        }

        return $document;
    }

    public function deleteDocument(PbcDocument $document): bool
    {
        try {
            // Delete from cloud storage if using cloudinary
            if ($this->cloudinaryService && $this->cloudinaryService->isConfigured()) {
                if (!empty($document->cloud_public_id)) {
                    $this->cloudinaryService->deleteFile($document->cloud_public_id);
                }
            }

            // Delete from local storage as backup
            $document->deleteFile();

            $this->logActivity('document_deleted', $document, auth()->user(), 'Document deleted');
        } catch (\Exception $e) {
            \Log::warning('Could not log document deletion: ' . $e->getMessage());
        }

        return $document->delete();
    }

    private function uploadSingleDocument(UploadedFile $file, int $pbcRequestId, User $user, ?string $comments, string $version): PbcDocument
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = 'pbc-documents/' . date('Y/m') . '/' . $fileName;

        $cloudUrl = null;
        $cloudPublicId = null;

        // Try uploading to Cloudinary first
        if ($this->cloudinaryService && $this->cloudinaryService->isConfigured()) {
            $cloudResult = $this->cloudinaryService->uploadFile($file, [
                'folder' => 'pbc-documents',
                'public_id' => 'pbc-documents/' . date('Y/m/') . pathinfo($originalName, PATHINFO_FILENAME) . '_' . time(),
                'tags' => ['pbc', 'request_' . $pbcRequestId, 'user_' . $user->id]
            ]);

            if ($cloudResult['success']) {
                $cloudUrl = $cloudResult['secure_url'];
                $cloudPublicId = $cloudResult['public_id'];
                \Log::info('File uploaded to Cloudinary: ' . $cloudPublicId);
            } else {
                \Log::warning('Cloudinary upload failed: ' . $cloudResult['error']);
            }
        }

        // Always store locally as backup
        $file->storeAs('pbc-documents/' . date('Y/m'), $fileName, 'public');

        // Create document record
        $document = PbcDocument::create([
            'pbc_request_id' => $pbcRequestId,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $user->id,
            'status' => 'pending',
            'comments' => $comments,
            'version' => $version,
            'is_latest_version' => true,
            'cloud_url' => $cloudUrl,
            'cloud_public_id' => $cloudPublicId,
        ]);

        // Mark previous versions as not latest
        PbcDocument::where('pbc_request_id', $pbcRequestId)
            ->where('original_name', $originalName)
            ->where('id', '!=', $document->id)
            ->update(['is_latest_version' => false]);

        try {
            $this->logActivity('document_uploaded', $document, $user, 'Document uploaded');
        } catch (\Exception $e) {
            \Log::warning('Could not log document upload: ' . $e->getMessage());
        }

        return $document->load(['pbcRequest', 'uploadedBy']);
    }

    public function getDocumentUrl(PbcDocument $document): string
    {
        // Return cloud URL if available, otherwise local URL
        if (!empty($document->cloud_url)) {
            return $document->cloud_url;
        }

        return $document->getFileUrl();
    }

    public function getDownloadUrl(PbcDocument $document): string
    {
        // For cloud storage, generate download URL
        if ($this->cloudinaryService && !empty($document->cloud_public_id)) {
            return $this->cloudinaryService->getDownloadUrl(
                $document->cloud_public_id,
                $document->original_name
            );
        }

        // For local storage, return direct download route
        return route('api.pbc-documents.download', $document);
    }

    private function getUserProjectIds(User $user): array
    {
        try {
            return \App\Models\Project::where(function ($query) use ($user) {
                $query->where('engagement_partner_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere('associate_1_id', $user->id)
                      ->orWhere('associate_2_id', $user->id);
            })->pluck('id')->toArray();
        } catch (\Exception $e) {
            \Log::warning('Could not get user project IDs: ' . $e->getMessage());
            return [];
        }
    }

    private function logActivity(string $action, PbcDocument $document, User $user, string $description): void
    {
        if (!class_exists('App\Models\AuditLog')) {
            return;
        }

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => PbcDocument::class,
                'model_id' => $document->id,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(User $user = null): array
    {
        $query = PbcDocument::query();

        if ($user && !$user->isSystemAdmin()) {
            if ($user->isGuest()) {
                $query->whereHas('pbcRequest', function ($q) use ($user) {
                    $q->where('assigned_to_id', $user->id);
                });
            } else {
                $projectIds = $this->getUserProjectIds($user);
                if (!empty($projectIds)) {
                    $query->whereHas('pbcRequest', function ($q) use ($projectIds) {
                        $q->whereIn('project_id', $projectIds);
                    });
                }
            }
        }

        $totalDocuments = $query->count();
        $totalSize = $query->sum('file_size');
        $pendingDocuments = $query->where('status', 'pending')->count();
        $approvedDocuments = $query->where('status', 'approved')->count();
        $rejectedDocuments = $query->where('status', 'rejected')->count();

        return [
            'total_documents' => $totalDocuments,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'pending_documents' => $pendingDocuments,
            'approved_documents' => $approvedDocuments,
            'rejected_documents' => $rejectedDocuments,
            'storage_usage_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
