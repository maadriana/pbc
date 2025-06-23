<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PbcRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'category_id',
        'title',
        'description',
        'requestor_id',
        'assigned_to_id',
        'date_requested',
        'due_date',
        'status',
        'priority',
        'notes',
        'rejection_reason',
        'completed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date_requested' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(PbcCategory::class, 'category_id');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(PbcDocument::class);
    }

    public function comments()
    {
        return $this->hasMany(PbcComment::class);
    }

    public function reminders()
    {
        return $this->hasMany(PbcReminder::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending')
                          ->where('due_date', '<', Carbon::today());
                    });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requestor_id', $userId);
    }

    public function scopeDueSoon($query, $days = 3)
    {
        return $query->where('due_date', '<=', Carbon::today()->addDays($days))
                    ->where('status', 'pending');
    }

    // Helper methods
    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date < Carbon::today();
    }

    public function isDueSoon($days = 3)
    {
        return $this->status === 'pending' &&
               $this->due_date <= Carbon::today()->addDays($days) &&
               $this->due_date >= Carbon::today();
    }

    public function getDaysUntilDue()
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function getDaysOverdue()
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return Carbon::today()->diffInDays($this->due_date);
    }

    public function markAsCompleted($userId = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'approved_by' => $userId,
            'approved_at' => $userId ? now() : null,
        ]);

        // Update project progress
        $this->project->updateProgress();
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'completed':
                return 'green';
            case 'pending':
                return $this->isOverdue() ? 'red' : 'yellow';
            case 'overdue':
                return 'red';
            case 'in_progress':
                return 'blue';
            case 'rejected':
                return 'red';
            default:
                return 'gray';
        }
    }

    public function getPriorityColorAttribute()
    {
        switch ($this->priority) {
            case 'urgent':
                return 'red';
            case 'high':
                return 'orange';
            case 'medium':
                return 'yellow';
            case 'low':
                return 'green';
            default:
                return 'gray';
        }
    }

    public function getDisplayStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getDisplayPriorityAttribute()
    {
        return ucfirst($this->priority);
    }
}
