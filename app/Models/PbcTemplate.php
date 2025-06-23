<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'engagement_type',
        'default_description',
        'default_days_to_complete',
        'default_priority',
        'required_fields',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'default_days_to_complete' => 'integer',
        'required_fields' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(PbcCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEngagementType($query, $type)
    {
        return $query->where('engagement_type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper methods
    public function createPbcRequest($projectId, $requestorId, $assignedToId, $customData = [])
    {
        $dueDate = now()->addDays($this->default_days_to_complete);

        return PbcRequest::create(array_merge([
            'project_id' => $projectId,
            'category_id' => $this->category_id,
            'title' => $this->name,
            'description' => $this->default_description,
            'requestor_id' => $requestorId,
            'assigned_to_id' => $assignedToId,
            'date_requested' => now(),
            'due_date' => $dueDate,
            'priority' => $this->default_priority,
            'status' => 'pending',
        ], $customData));
    }

    public function getDisplayEngagementTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->engagement_type));
    }

    public function getDisplayPriorityAttribute()
    {
        return ucfirst($this->default_priority);
    }
}
