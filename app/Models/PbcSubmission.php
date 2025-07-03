<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PbcSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pbc_request_item_id',
        'pbc_request_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'uploaded_by',
        'uploaded_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'review_action',
        'version',
        'replaces_submission_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
        'version' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function pbcRequestItem()
    {
        return $this->belongsTo(PbcRequestItem::class, 'pbc_request_item_id');
    }

    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class, 'pbc_request_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replacedSubmission()
    {
        return $this->belongsTo(PbcSubmission::class, 'replaces_submission_id');
    }

    public function replacements()
    {
        return $this->hasMany(PbcSubmission::class, 'replaces_submission_id');
    }

    public function comments()
    {
        return $this->morphMany(PbcComment::class, 'commentable');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    public function scopeByUploader($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeByRequestItem($query, $itemId)
    {
        return $query->where('pbc_request_item_id', $itemId);
    }

    // Helper methods
    public function getFileUrl()
    {
        return Storage::disk('pbc-documents')->url($this->file_path);
    }

 public function getDownloadUrl()
{
    return route('pbc-submissions.download', ['pbcSubmission' => $this->id]);
}

public function getPreviewUrl()
{
    return route('pbc-submissions.preview', ['pbcSubmission' => $this->id]);
}

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileExtension()
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    public function getFileIcon()
    {
        $extension = strtolower($this->getFileExtension());

        return match($extension) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-excel',
            'ppt', 'pptx' => 'file-powerpoint',
            'jpg', 'jpeg', 'png', 'gif' => 'file-image',
            'zip', 'rar', '7z' => 'file-archive',
            'txt' => 'file-text',
            'csv' => 'file-csv',
            default => 'file'
        };
    }

    public function isImage()
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($this->mime_type, $imageTypes);
    }

    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    public function canBePreviewedInBrowser()
    {
        return $this->isImage() || $this->isPdf();
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'pending' => 'badge-secondary',
            'under_review' => 'badge-warning',
            'accepted' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function canBeDownloadedBy(User $user)
    {
        // System admin can download all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Uploader can download
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Project team members can download
        $teamMemberIds = $this->pbcRequest->project->getTeamMembers()->pluck('id')->toArray();
        if (in_array($user->id, $teamMemberIds)) {
            return true;
        }

        // Assigned user can download
        if ($this->pbcRequestItem->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    public function canBeReviewedBy(User $user)
    {
        // Only staff members can review
        if ($user->isGuest()) {
            return false;
        }

        // System admin can review all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Project team members with proper permissions can review
        $teamMemberIds = $this->pbcRequest->project->getTeamMembers()->pluck('id')->toArray();
        if (in_array($user->id, $teamMemberIds) && $user->canApproveDocuments()) {
            return true;
        }

        return false;
    }

    public function canBeDeletedBy(User $user)
    {
        // System admin can delete all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Uploader can delete if not yet reviewed
        if ($this->uploaded_by === $user->id && $this->status === 'pending') {
            return true;
        }

        // Project managers and engagement partners can delete
        $project = $this->pbcRequest->project;
        if (in_array($user->id, [$project->engagement_partner_id, $project->manager_id])) {
            return true;
        }

        return false;
    }

    public function markAsUnderReview($reviewedBy)
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewedBy,
        ]);

        // Update parent item status
        $this->pbcRequestItem->update(['status' => 'under_review']);

        return $this;
    }

    public function approve($reviewedBy, $remarks = null)
    {
        $this->update([
            'status' => 'accepted',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_action' => 'approve',
            'review_remarks' => $remarks,
        ]);

        // Update parent item status
        $this->pbcRequestItem->accept($reviewedBy, $remarks);

        // Log the activity
        AuditLog::create([
            'user_id' => $reviewedBy,
            'action' => 'approved',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "File '{$this->original_filename}' approved for PBC item '{$this->pbcRequestItem->getDisplayName()}'",
            'category' => 'document',
            'severity' => 'medium',
        ]);

        return $this;
    }

    public function reject($reviewedBy, $remarks)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_action' => 'reject',
            'review_remarks' => $remarks,
        ]);

        // Update parent item status
        $this->pbcRequestItem->reject($reviewedBy, $remarks);

        // Log the activity
        AuditLog::create([
            'user_id' => $reviewedBy,
            'action' => 'rejected',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "File '{$this->original_filename}' rejected for PBC item '{$this->pbcRequestItem->getDisplayName()}': {$remarks}",
            'category' => 'document',
            'severity' => 'medium',
        ]);

        return $this;
    }

    public function requestRevision($reviewedBy, $remarks)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_action' => 'request_revision',
            'review_remarks' => $remarks,
        ]);

        // Reset parent item to pending for revision
        $this->pbcRequestItem->update(['status' => 'pending']);

        return $this;
    }

    public function createNewVersion($newFileData, $uploadedBy)
    {
        $newVersion = static::create([
            'pbc_request_item_id' => $this->pbc_request_item_id,
            'pbc_request_id' => $this->pbc_request_id,
            'original_filename' => $newFileData['original_filename'],
            'stored_filename' => $newFileData['stored_filename'],
            'file_path' => $newFileData['file_path'],
            'mime_type' => $newFileData['mime_type'],
            'file_size' => $newFileData['file_size'],
            'file_hash' => $newFileData['file_hash'],
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
            'status' => 'pending',
            'version' => $this->version + 1,
            'replaces_submission_id' => $this->id,
            'metadata' => $newFileData['metadata'] ?? null,
            'is_active' => true,
        ]);

        // Mark old version as inactive
        $this->update(['is_active' => false]);

        // Update parent item status to submitted
        $this->pbcRequestItem->update(['status' => 'submitted']);

        return $newVersion;
    }

    public function archive()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function restore()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    public function physicallyDelete()
    {
        // Delete the actual file
        Storage::disk('pbc-documents')->delete($this->file_path);

        // Soft delete the record
        $this->delete();

        return true;
    }

    public function getDuplicates()
    {
        return static::where('file_hash', $this->file_hash)
                    ->where('id', '!=', $this->id)
                    ->where('is_active', true)
                    ->get();
    }

    public function hasDuplicates()
    {
        return $this->getDuplicates()->count() > 0;
    }

    public function getVersionHistory()
    {
        return static::where('pbc_request_item_id', $this->pbc_request_item_id)
                    ->orderBy('version', 'desc')
                    ->get();
    }

    public function isLatestVersion()
    {
        $latestVersion = static::where('pbc_request_item_id', $this->pbc_request_item_id)
                              ->max('version');
        return $this->version === $latestVersion;
    }

    public function getUploadedAtFormatted()
    {
        return $this->uploaded_at->format('M j, Y \a\t g:i A');
    }

    public function getReviewedAtFormatted()
    {
        return $this->reviewed_at ? $this->reviewed_at->format('M j, Y \a\t g:i A') : null;
    }
}
