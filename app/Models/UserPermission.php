<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission',
        'resource',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByPermission($query, $permission)
    {
        return $query->where('permission', $permission);
    }

    public function scopeByResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    // Helper methods
    public function getDisplayPermissionAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->permission));
    }
}
