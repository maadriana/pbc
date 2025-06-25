<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'entity',
        'role',
        'access_level',
        'contact_number',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'access_level' => 'integer',
    ];

    // Relationships
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function requestedPbcs()
    {
        return $this->hasMany(PbcRequest::class, 'requestor_id');
    }

    public function assignedPbcs()
    {
        return $this->hasMany(PbcRequest::class, 'assigned_to_id');
    }

    public function approvedPbcs()
    {
        return $this->hasMany(PbcRequest::class, 'approved_by');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(PbcDocument::class, 'uploaded_by');
    }

    public function reviewedDocuments()
    {
        return $this->hasMany(PbcDocument::class, 'reviewed_by');
    }

    public function comments()
    {
        return $this->hasMany(PbcComment::class);
    }

    public function sentReminders()
    {
        return $this->hasMany(PbcReminder::class, 'sent_by');
    }

    public function receivedReminders()
    {
        return $this->hasMany(PbcReminder::class, 'sent_to');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function projectsAsEngagementPartner()
    {
        return $this->hasMany(Project::class, 'engagement_partner_id');
    }

    public function projectsAsManager()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function projectsAsAssociate1()
    {
        return $this->hasMany(Project::class, 'associate_1_id');
    }

    public function projectsAsAssociate2()
    {
        return $this->hasMany(Project::class, 'associate_2_id');
    }

    public function teamAssignments()
    {
        return $this->hasMany(ProjectTeamAssignment::class);
    }

    public function createdTemplates()
    {
        return $this->hasMany(PbcTemplate::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByAccessLevel($query, $level)
    {
        return $query->where('access_level', $level);
    }

    // Helper methods - UPDATED
   // Update your hasPermission method in User.php to include the manage_categories permission:

public function hasPermission($permission, $resource = null)
{
    // If system admin, grant all permissions
    if ($this->isSystemAdmin()) {
        return true;
    }

    // Check database permissions first (if you have a permissions table)
    $hasDbPermission = $this->permissions()
        ->where('permission', $permission)
        ->when($resource, function ($query) use ($resource) {
            return $query->where('resource', $resource);
        })
        ->exists();

    if ($hasDbPermission) {
        return true;
    }

    // Fallback to role-based permissions for PBC system
    $rolePermissions = [
        'engagement_partner' => [
            'view_client', 'create_client', 'edit_client', 'delete_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'approve_document', 'send_reminder', 'view_audit_log',
            'manage_categories'
        ],
        'manager' => [
            'view_client', 'create_client', 'edit_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'approve_document', 'send_reminder',
            'manage_categories'
        ],
        'associate' => [
            'view_project', 'create_project', 'edit_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'approve_document', 'send_reminder'
        ],
        'guest' => [
            'view_pbc_request', 'edit_pbc_request', 'upload_document'
        ],
    ];

    return in_array($permission, $rolePermissions[$this->role] ?? []);
}

    public function isSystemAdmin()
    {
        return $this->role === 'system_admin';
    }

    public function isEngagementPartner()
    {
        return $this->role === 'engagement_partner';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isAssociate()
    {
        return $this->role === 'associate';
    }

    public function isGuest()
    {
        return $this->role === 'guest';
    }

    public function canManageUsers()
    {
        return $this->isSystemAdmin();
    }

    public function canManageClients()
    {
        return in_array($this->role, ['system_admin', 'engagement_partner']);
    }

    public function canApproveDocuments()
    {
        return in_array($this->role, ['system_admin', 'engagement_partner', 'manager']);
    }

    public function getDisplayRoleAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->role));
    }

    public function unreadNotifications()
    {
        return $this->morphMany('Illuminate\Notifications\DatabaseNotification', 'notifiable')
                    ->whereNull('read_at');
    }

    public function getAllPermissionsAttribute()
    {
        // If system admin, return all permissions
        if ($this->isSystemAdmin()) {
            return collect([
                'view_user', 'create_user', 'edit_user', 'delete_user',
                'view_client', 'create_client', 'edit_client', 'delete_client',
                'view_project', 'create_project', 'edit_project', 'delete_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'delete_document',
                'send_reminder', 'view_audit_log', 'export_reports',
                'manage_settings', 'manage_permissions'
            ]);
        }

        return $this->permissions ? $this->permissions->pluck('permission') : collect();
    }
}
