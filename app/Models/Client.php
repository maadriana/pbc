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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function projects()
    {
        return $this->hasMany(Project::class);
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

    public function getTotalPbcRequestsCount()
    {
        return PbcRequest::whereHas('project', function ($query) {
            $query->where('client_id', $this->id);
        })->count();
    }

    public function getPendingPbcRequestsCount()
    {
        return PbcRequest::whereHas('project', function ($query) {
            $query->where('client_id', $this->id);
        })->where('status', 'pending')->count();
    }
}
