<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'template_id',
        'title',
        'client_name',
        'audit_period',
        'contact_person',
        'contact_email',
        'engagement_partner',
        'engagement_manager',
        'document_date',
        'status',
        'completion_percentage',
        'total_items',
        'completed_items',
        'pending_items',
        'overdue_items',
        'created_by',
        'assigned_to',
        'due_date',
        'completed_at',
        'notes',
        'client_notes',
        'status_note',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'completion_percentage' => 'decimal:2',
        'total_items' => 'integer',
        'completed_items' => 'integer',
        'pending_items' => 'integer',
        'overdue_items' => 'integer',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function template()
    {
        return $this->belongsTo(PbcTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(PbcRequestItem::class, 'pbc_request_id')->orderBy('sort_order');
    }

    public function submissions()
    {
        return $this->hasMany(PbcSubmission::class, 'pbc_request_id');
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
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['draft', 'active']);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Helper methods
    public function updateProgress()
    {
        $totalItems = $this->items()->count();
        $completedItems = $this->items()->where('status', 'accepted')->count();
        $pendingItems = $this->items()->whereIn('status', ['pending', 'submitted', 'under_review'])->count();
        $overdueItems = $this->items()->where('status', 'overdue')->count();

        $completionPercentage = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;

        $this->update([
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'pending_items' => $pendingItems,
            'overdue_items' => $overdueItems,
            'completion_percentage' => round($completionPercentage, 2),
        ]);

        // Update project progress as well
        $this->project->updateProgress();

        return $this;
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
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
            'draft' => 'badge-secondary',
            'active' => 'badge-primary',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function canBeEditedBy(User $user)
    {
        // System admin can edit all
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Creator can edit
        if ($this->created_by === $user->id) {
            return true;
        }

        // Project team members can edit
        $teamMemberIds = $this->project->getTeamMembers()->pluck('id')->toArray();
        if (in_array($user->id, $teamMemberIds)) {
            return true;
        }

        return false;
    }

    public function canBeViewedBy(User $user)
    {
        // If can edit, can definitely view
        if ($this->canBeEditedBy($user)) {
            return true;
        }

        // Assigned user can view
        if ($this->assigned_to === $user->id) {
            return true;
        }

        // Guest users can view if they're the client contact
        if ($user->isGuest() && $user->email === $this->contact_email) {
            return true;
        }

        return false;
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->updateProgress();

        return $this;
    }

    public function getItemsGroupedByCategory()
    {
        return $this->items()
            ->with(['category', 'parent'])
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category.name');
    }
}
