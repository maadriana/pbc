<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sec_registration_no',
        'industry_classification',
        'business_address',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_number',
        'secondary_contact_name',
        'secondary_contact_email',
        'secondary_contact_number',
        'is_active',
        // NEW PBC fields
        'total_pbc_requests',
        'pending_pbc_requests',
        'average_pbc_completion_rate',
        'last_pbc_activity',
        'pbc_preferences',
        'special_instructions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_pbc_requests' => 'integer',           // NEW
        'pending_pbc_requests' => 'integer',         // NEW
        'average_pbc_completion_rate' => 'decimal:2', // NEW
        'last_pbc_activity' => 'datetime',           // NEW
        'pbc_preferences' => 'array',                // NEW
    ];

    // Relationships
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    // NEW: Direct relationship to PBC requests through projects
    public function pbcRequests()
    {
        return $this->hasManyThrough(PbcRequest::class, Project::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry_classification', $industry);
    }

    // Helper methods
    public function getActiveProjectsCount()
    {
        return $this->projects()->where('status', 'active')->count();
    }

    // FIXED: Use direct relationship instead of complex query
    public function getTotalPbcRequestsCount()
    {
        return $this->pbcRequests()->count();
    }

    // FIXED: Specify table name to avoid ambiguous column reference
    public function getPendingPbcRequestsCount()
    {
        return $this->pbcRequests()->whereIn('pbc_requests.status', ['draft', 'active'])->count();
    }

    public function getCompletedPbcRequestsCount()
    {
        return $this->pbcRequests()->where('pbc_requests.status', 'completed')->count();
    }

    // NEW: PBC-specific methods
    public function updatePbcStatistics()
    {
        $totalPbcRequests = $this->getTotalPbcRequestsCount();
        $pendingPbcRequests = $this->getPendingPbcRequestsCount();
        $completedPbcRequests = $this->getCompletedPbcRequestsCount();

        // Calculate average completion rate
        $averageCompletionRate = $totalPbcRequests > 0
            ? ($completedPbcRequests / $totalPbcRequests) * 100
            : 0;

        $this->update([
            'total_pbc_requests' => $totalPbcRequests,
            'pending_pbc_requests' => $pendingPbcRequests,
            'average_pbc_completion_rate' => round($averageCompletionRate, 2),
            'last_pbc_activity' => now(),
        ]);

        return $this;
    }

    public function getActivePbcRequestsCount()
    {
        return $this->pbcRequests()->where('pbc_requests.status', 'active')->count();
    }

    public function getOverduePbcRequestsCount()
    {
        return $this->pbcRequests()
            ->where('pbc_requests.due_date', '<', now())
            ->whereIn('pbc_requests.status', ['draft', 'active'])
            ->count();
    }

    public function getPbcCompletionRate()
    {
        $completed = $this->getCompletedPbcRequestsCount();
        $total = $this->getTotalPbcRequestsCount();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    public function getRecentPbcActivity($days = 30)
    {
        return $this->pbcRequests()
            ->where(function($query) use ($days) {
                $query->where('pbc_requests.created_at', '>=', now()->subDays($days))
                      ->orWhere('pbc_requests.updated_at', '>=', now()->subDays($days));
            })
            ->with(['project', 'creator', 'assignedTo'])
            ->orderBy('pbc_requests.updated_at', 'desc')
            ->get();
    }

    public function getPrimaryContactUser()
    {
        return User::where('email', $this->primary_contact_email)->first();
    }

    public function getSecondaryContactUser()
    {
        return User::where('email', $this->secondary_contact_email)->first();
    }

    public function getPbcPreference($key, $default = null)
    {
        $preferences = $this->pbc_preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    public function setPbcPreference($key, $value)
    {
        $preferences = $this->pbc_preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['pbc_preferences' => $preferences]);

        return $this;
    }

    public function hasSpecialInstructions()
    {
        return !empty($this->special_instructions);
    }

    public function getDisplayIndustryAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->industry_classification));
    }

    // Performance indicators for dashboard
    public function getPbcPerformanceScore()
    {
        $completionRate = $this->average_pbc_completion_rate;
        $overdueCount = $this->getOverduePbcRequestsCount();
        $totalActive = $this->getActivePbcRequestsCount();

        // Base score from completion rate
        $score = $completionRate;

        // Penalty for overdue items
        if ($totalActive > 0) {
            $overdueRatio = $overdueCount / $totalActive;
            $score -= ($overdueRatio * 30); // Up to 30 point penalty
        }

        return max(0, min(100, $score)); // Keep between 0-100
    }

    public function getPbcStatusSummary()
    {
        return [
            'total_requests' => $this->total_pbc_requests,
            'pending_requests' => $this->pending_pbc_requests,
            'completed_requests' => $this->getCompletedPbcRequestsCount(),
            'overdue_requests' => $this->getOverduePbcRequestsCount(),
            'completion_rate' => $this->average_pbc_completion_rate,
            'performance_score' => $this->getPbcPerformanceScore(),
            'last_activity' => $this->last_pbc_activity,
        ];
    }
}
