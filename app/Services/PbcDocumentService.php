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
    public function getFilteredDocuments(array $filters, User $user): LengthAwarePaginator
    {
        $query = PbcDocument::with(['pbcRequest.project.client', 'uploadedBy', 'reviewedBy'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('original_name', 'like', "%{$search}%")
                      ->orWhere('file_type', 'like', "%{$search}%");
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
            });

        // Apply user-based filtering
        if ($user->isGuest()) {
            $query->whereHas('pbcRequest', function ($q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        } elseif (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $projectIds = $this->getUserProjectIds($user);
            $query->whereHas('pbcRequest', function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds);
            });
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
            $document = $this->uploadSingleDocument($file, $pbcRequestId, $user, $comments, $version);
            $uploadedDocuments[] = $document;
        }

        return $uploadedDocuments;
    }

    public function approveDocument(PbcDocument $document, User $user, ?string $comments = null): PbcDocument
    {
        $document->approve($user->id, $comments);

        $this->logActivity('document_approved', $document, $user, 'Document approved');

        return $document;
    }

    public function rejectDocument(PbcDocument $document, User $user, string $reason): PbcDocument
    {
        $document->reject($user->id, $reason);

        $this->logActivity('document_rejected', $document, $user, 'Document rejected');

        return $document;
    }

    public function deleteDocument(PbcDocument $document): bool
    {
        $this->logActivity('document_deleted', $document, auth()->user(), 'Document deleted');

        return $document->delete();
    }

    private function uploadSingleDocument(UploadedFile $file, int $pbcRequestId, User $user, ?string $comments, string $version): PbcDocument
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = 'pbc-documents/' . date('Y/m') . '/' . $fileName;

        // Store the file
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
        ]);

        // Mark previous versions as not latest
        PbcDocument::where('pbc_request_id', $pbcRequestId)
            ->where('id', '!=', $document->id)
            ->update(['is_latest_version' => false]);

        $this->logActivity('document_uploaded', $document, $user, 'Document uploaded');

        return $document->load(['pbcRequest', 'uploadedBy']);
    }

    private function getUserProjectIds(User $user): array
    {
        return \App\Models\Project::where(function ($query) use ($user) {
            $query->where('engagement_partner_id', $user->id)
                  ->orWhere('manager_id', $user->id)
                  ->orWhere('associate_1_id', $user->id)
                  ->orWhere('associate_2_id', $user->id);
        })->pluck('id')->toArray();
    }

    private function logActivity(string $action, PbcDocument $document, User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => PbcDocument::class,
            'model_id' => $document->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
