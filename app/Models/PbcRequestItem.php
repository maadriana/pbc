<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_id',
        'template_item_id',
        'category_id',
        'parent_id',
        'item_number',
        'sub_item_letter',
        'description',
        'sort_order',
        'status',
        'date_requested',
        'due_date',
        'date_submitted',
        'date_reviewed',
        'days_outstanding',
        'requested_by',
        'assigned_to',
        'reviewed_by',
        'remarks',
        'client_remarks',
        'is_required',
        'is_custom',
    ];

    protected $casts = [
        'date_requested' => 'date',
        'due_date' => 'date',
        'date_submitted' => 'datetime',
        'date_reviewed' => 'datetime',
        'days_outstanding' => 'integer',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'is_custom' => 'boolean',
    ];

    // Relationships
    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class, 'pbc_request_id');
    }

    public function templateItem()
    {
        return $this->belongsTo(PbcTemplateItem::class, 'template_item_id');
    }

    public function category()
    {
        return $this->belongsTo(PbcCategory::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(PbcRequestItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PbcRequestItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submissions()
    {
        return $this->hasMany(PbcSubmission::class, 'pbc_request_item_id');
    }

    public function comments()
    {
        return $this->morphMany(PbcComment::class, 'commentable');
    }

    public function reminders()
    {
        return $this->morphMany(PbcReminder::class, 'remindable');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
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

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['accepted']);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopeMainItems($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubItems($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public function updateDaysOutstanding()
    {
        if ($this->date_requested && $this->status !== 'accepted') {
            $this->days_outstanding = now()->diffInDays($this->date_requested);
            $this->save();
        }

        return $this;
    }

    public function getFullItemNumber()
    {
        if ($this->parent_id) {
            return $this->parent->item_number . $this->sub_item_letter;
        }
        return $this->item_number;
    }

    public function getDisplayName()
    {
        $prefix = $this->getFullItemNumber();
        return $prefix ? "{$prefix}. {$this->description}" : $this->description;
    }

    public function isMainItem()
    {
        return is_null($this->parent_id);
    }

    public function isSubItem()
    {
        return !is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['accepted']);
    }

    public function getDaysUntilDue()
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'pending' => 'badge-secondary',
            'submitted' => 'badge-info',
            'under_review' => 'badge-warning',
            'accepted' => 'badge-success',
            'rejected' => 'badge-danger',
            'overdue' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function canBeEditedBy(User $user)
    {
        // System admin can edit all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Requestor can edit if not yet submitted
        if ($this->requested_by === $user->id && in_array($this->status, ['pending'])) {
            return true;
        }

        // Project team members can edit
        $teamMemberIds = $this->pbcRequest->project->getTeamMembers()->pluck('id')->toArray();
        if (in_array($user->id, $teamMemberIds)) {
            return true;
        }

        return false;
    }

    public function canUploadFilesBy(User $user)
    {
        // System admin can upload
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Assigned user can upload
        if ($this->assigned_to === $user->id) {
            return true;
        }

        // Client contact can upload
        if ($user->email === $this->pbcRequest->contact_email) {
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

    public function submitForReview($submittedBy)
    {
        $this->update([
            'status' => 'submitted',
            'date_submitted' => now(),
        ]);

        // Update parent request progress
        $this->pbcRequest->updateProgress();

        // Log the activity
        AuditLog::create([
            'user_id' => $submittedBy,
            'action' => 'submitted',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "PBC item '{$this->getDisplayName()}' submitted for review",
            'category' => 'pbc_request',
            'severity' => 'medium',
        ]);

        return $this;
    }

    public function markAsUnderReview($reviewedBy)
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewedBy,
        ]);

        return $this;
    }

    public function accept($reviewedBy, $remarks = null)
    {
        $this->update([
            'status' => 'accepted',
            'date_reviewed' => now(),
            'reviewed_by' => $reviewedBy,
            'remarks' => $remarks,
        ]);

        // Update parent request progress
        $this->pbcRequest->updateProgress();

        // Log the activity
        AuditLog::create([
            'user_id' => $reviewedBy,
            'action' => 'accepted',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "PBC item '{$this->getDisplayName()}' accepted",
            'category' => 'pbc_request',
            'severity' => 'medium',
        ]);

        return $this;
    }

    public function reject($reviewedBy, $remarks)
    {
        $this->update([
            'status' => 'rejected',
            'date_reviewed' => now(),
            'reviewed_by' => $reviewedBy,
            'remarks' => $remarks,
        ]);

        // Update parent request progress
        $this->pbcRequest->updateProgress();

        // Log the activity
        AuditLog::create([
            'user_id' => $reviewedBy,
            'action' => 'rejected',
            'model_type' => self::class,
            'model_id' => $this->id,
            'description' => "PBC item '{$this->getDisplayName()}' rejected: {$remarks}",
            'category' => 'pbc_request',
            'severity' => 'medium',
        ]);

        return $this;
    }

    public function resetToPending($userId)
    {
        $this->update([
            'status' => 'pending',
            'date_submitted' => null,
            'date_reviewed' => null,
            'reviewed_by' => null,
        ]);

        // Update parent request progress
        $this->pbcRequest->updateProgress();

        return $this;
    }

    public function getActiveSubmissions()
    {
        return $this->submissions()->where('is_active', true)->orderBy('version', 'desc')->get();
    }

    public function getLatestSubmission()
    {
        return $this->submissions()->where('is_active', true)->orderBy('version', 'desc')->first();
    }

    public function hasActiveSubmissions()
    {
        return $this->submissions()->where('is_active', true)->exists();
    }

    public function getCompletionPercentage()
    {
        $acceptedSubmissions = $this->submissions()->where('status', 'accepted')->count();
        $totalSubmissions = $this->submissions()->count();

        if ($totalSubmissions === 0) {
            return 0;
        }

        return ($acceptedSubmissions / $totalSubmissions) * 100;
    }

    public function duplicate($newPbcRequestId, $userId)
    {
        $newItem = $this->replicate();
        $newItem->pbc_request_id = $newPbcRequestId;
        $newItem->status = 'pending';
        $newItem->date_submitted = null;
        $newItem->date_reviewed = null;
        $newItem->reviewed_by = null;
        $newItem->remarks = null;
        $newItem->is_custom = true; // Mark as custom since it's manually added
        $newItem->requested_by = $userId;
        $newItem->save();

        return $newItem;
    }
}
