<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'engagement_type',
        'engagement_period',
        'contact_person',
        'contact_email',
        'contact_number',
        'engagement_partner_id',
        'manager_id',
        'associate_1_id',
        'associate_2_id',
        'status',
        'progress_percentage',
        // NEW PBC fields
        'total_pbc_requests',
        'completed_pbc_requests',
        'pbc_completion_percentage',
        'pbc_deadline',
        'pbc_status',
        'pbc_settings',
        'notes',
    ];

    protected $casts = [
        'engagement_period' => 'date',
        'progress_percentage' => 'decimal:2',
        'pbc_completion_percentage' => 'decimal:2',  // NEW
        'pbc_deadline' => 'date',                    // NEW
        'pbc_settings' => 'array',                   // NEW
        'total_pbc_requests' => 'integer',           // NEW
        'completed_pbc_requests' => 'integer',       // NEW
    ];

    // Add this accessor for display name
    public function getDisplayNameAttribute()
    {
        return ucfirst($this->engagement_type) . ' - ' . $this->engagement_period->format('Y');
    }

    // Add this accessor for compatibility
    public function getNameAttribute()
    {
        return $this->display_name;
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function engagementPartner()
    {
        return $this->belongsTo(User::class, 'engagement_partner_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function associate1()
    {
        return $this->belongsTo(User::class, 'associate_1_id');
    }

    public function associate2()
    {
        return $this->belongsTo(User::class, 'associate_2_id');
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }

    public function teamAssignments()
    {
        return $this->hasMany(ProjectTeamAssignment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEngagementType($query, $type)
    {
        return $query->where('engagement_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPbcStatus($query, $pbcStatus)
    {
        return $query->where('pbc_status', $pbcStatus);
    }

    // Helper methods
    public function getTeamMembers()
    {
        $members = collect();

        if ($this->engagementPartner) {
            $members->push($this->engagementPartner);
        }
        if ($this->manager) {
            $members->push($this->manager);
        }
        if ($this->associate1) {
            $members->push($this->associate1);
        }
        if ($this->associate2) {
            $members->push($this->associate2);
        }

        return $members->unique('id');
    }

    // UPDATED: Enhanced progress calculation
    public function updateProgress()
    {
        // Update general project progress
        $totalRequests = $this->pbcRequests()->count();
        $completedRequests = $this->pbcRequests()->where('status', 'completed')->count();

        if ($totalRequests > 0) {
            $this->progress_percentage = ($completedRequests / $totalRequests) * 100;
        }

        // Update PBC-specific progress
        $this->updatePbcProgress();

        $this->save();
    }

    // NEW: PBC-specific progress calculation
    public function updatePbcProgress()
    {
        $totalPbcRequests = $this->pbcRequests()->count();
        $completedPbcRequests = $this->pbcRequests()->where('status', 'completed')->count();
        $activePbcRequests = $this->pbcRequests()->where('status', 'active')->count();

        // Calculate PBC completion percentage
        $pbcCompletionPercentage = $totalPbcRequests > 0
            ? ($completedPbcRequests / $totalPbcRequests) * 100
            : 0;

        // Determine PBC status
        $pbcStatus = 'not_started';
        if ($totalPbcRequests > 0) {
            if ($completedPbcRequests === $totalPbcRequests) {
                $pbcStatus = 'completed';
            } elseif ($activePbcRequests > 0 || $completedPbcRequests > 0) {
                $pbcStatus = 'in_progress';
            }

            // Check if overdue
            if ($this->pbc_deadline && $this->pbc_deadline->isPast() && $pbcStatus !== 'completed') {
                $pbcStatus = 'overdue';
            }
        }

        $this->update([
            'total_pbc_requests' => $totalPbcRequests,
            'completed_pbc_requests' => $completedPbcRequests,
            'pbc_completion_percentage' => round($pbcCompletionPercentage, 2),
            'pbc_status' => $pbcStatus,
        ]);

        // Update client statistics
        $this->client->updatePbcStatistics();

        return $this;
    }

    public function getDisplayEngagementTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->engagement_type));
    }

    public function getDisplayStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    // NEW: PBC-specific helper methods
    public function getDisplayPbcStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->pbc_status));
    }

    public function isPbcOverdue()
    {
        return $this->pbc_deadline && $this->pbc_deadline->isPast() && $this->pbc_status !== 'completed';
    }

    public function getDaysUntilPbcDeadline()
    {
        if (!$this->pbc_deadline) {
            return null;
        }

        return now()->diffInDays($this->pbc_deadline, false);
    }

    public function getPbcStatusBadgeClass()
    {
        return match($this->pbc_status) {
            'not_started' => 'badge-secondary',
            'in_progress' => 'badge-primary',
            'completed' => 'badge-success',
            'overdue' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function createPbcRequestFromTemplate($templateId, $createdBy, $assignedTo = null)
    {
        $template = PbcTemplate::find($templateId);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        // Check if template can be used for this engagement type
        if (!$template->canBeUsedForEngagement($this->engagement_type)) {
            throw new \Exception('Template cannot be used for this engagement type');
        }

        $pbcRequest = PbcRequest::create([
            'project_id' => $this->id,
            'template_id' => $templateId,
            'title' => "{$template->name} - {$this->client->name} {$this->engagement_period->format('Y')}",
            'client_name' => $this->client->name,
            'audit_period' => $this->engagement_period->format('Y-m-d'),
            'contact_person' => $this->contact_person,
            'contact_email' => $this->contact_email,
            'engagement_partner' => $this->engagementPartner?->name,
            'engagement_manager' => $this->manager?->name,
            'document_date' => now(),
            'status' => 'draft',
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'due_date' => $this->pbc_deadline,
        ]);

        // Create request items from template items
        $templateItems = $template->templateItems()->with('category')->orderBy('sort_order')->get();

        foreach ($templateItems as $templateItem) {
            PbcRequestItem::create([
                'pbc_request_id' => $pbcRequest->id,
                'template_item_id' => $templateItem->id,
                'category_id' => $templateItem->category_id,
                'parent_id' => null, // We'll set this after creating all items
                'item_number' => $templateItem->item_number,
                'sub_item_letter' => $templateItem->sub_item_letter,
                'description' => $templateItem->description,
                'sort_order' => $templateItem->sort_order,
                'is_required' => $templateItem->is_required,
                'requested_by' => $createdBy,
                'assigned_to' => $assignedTo,
                'date_requested' => now(),
            ]);
        }

        // Update progress
        $pbcRequest->updateProgress();
        $this->updatePbcProgress();

        return $pbcRequest;
    }

    public function getPbcSetting($key, $default = null)
    {
        $settings = $this->pbc_settings ?? [];
        return $settings[$key] ?? $default;
    }
}
