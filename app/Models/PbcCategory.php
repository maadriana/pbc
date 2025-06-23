<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PbcCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'category_id');
    }

    public function templates()
    {
        return $this->hasMany(PbcTemplate::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Helper methods
    public function getRequestsCount()
    {
        return $this->pbcRequests()->count();
    }

    public function getPendingRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'pending')->count();
    }

    public function getCompletedRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'completed')->count();
    }
}
