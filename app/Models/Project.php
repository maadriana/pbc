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
        'notes',
    ];

    protected $casts = [
        'engagement_period' => 'date',
        'progress_percentage' => 'decimal:2',
    ];

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

    public function updateProgress()
    {
        $totalRequests = $this->pbcRequests()->count();
        $completedRequests = $this->pbcRequests()->where('status', 'completed')->count();

        if ($totalRequests > 0) {
            $this->progress_percentage = ($completedRequests / $totalRequests) * 100;
            $this->save();
        }
    }

    public function getDisplayEngagementTypeAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->engagement_type));
    }

    public function getDisplayStatusAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
