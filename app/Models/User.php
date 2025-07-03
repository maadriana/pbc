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
        'position_title',        // NEW
        'department',            // NEW
        'role',
        'access_level',
        'contact_number',
        'notification_preferences', // NEW
        'pbc_settings',            // NEW
        'is_active',
        'last_login_at',          // NEW
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'access_level' => 'integer',
        'notification_preferences' => 'array',  // NEW
        'pbc_settings' => 'array',              // NEW
        'last_login_at' => 'datetime',          // NEW
    ];

    // Relationships
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    // UPDATED PBC Relationships - Fixed method names to match new table structure
    public function createdPbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'created_by');
    }

    public function assignedPbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'assigned_to');
    }

    public function createdPbcTemplates()
    {
        return $this->hasMany(PbcTemplate::class, 'created_by');
    }

    public function requestedPbcItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'requested_by');
    }

    public function assignedPbcItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'assigned_to');
    }

    public function reviewedPbcItems()
    {
        return $this->hasMany(PbcRequestItem::class, 'reviewed_by');
    }

    public function uploadedSubmissions()
    {
        return $this->hasMany(PbcSubmission::class, 'uploaded_by');
    }

    public function reviewedSubmissions()
    {
        return $this->hasMany(PbcSubmission::class, 'reviewed_by');
    }

    public function pbcComments()
    {
        return $this->hasMany(PbcComment::class, 'user_id');
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
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    // Project relationships
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
                'manage_categories', 'send_messages', 'view_messages', 'create_conversations', 'receive_notifications'
            ],
            'manager' => [
                'view_client', 'create_client', 'edit_client',
                'view_project', 'create_project', 'edit_project', 'delete_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'send_reminder',
                'manage_categories', 'send_messages', 'view_messages', 'create_conversations', 'receive_notifications'
            ],
            'associate' => [
                'view_project', 'create_project', 'edit_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'send_reminder', 'send_messages', 'view_messages', 'receive_notifications'
            ],
            'guest' => [
                'view_pbc_request', 'edit_pbc_request', 'upload_document', 'view_messages', 'receive_notifications'
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

    // NEW: PBC-specific helper methods
    public function getActivePbcRequestsCount()
    {
        return $this->assignedPbcRequests()->where('status', 'active')->count();
    }

    public function getPendingPbcItemsCount()
    {
        return $this->assignedPbcItems()->whereIn('status', ['pending', 'submitted', 'under_review'])->count();
    }

    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function getNotificationPreference($type)
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences[$type] ?? true; // Default to true
    }

    public function getPbcSetting($key, $default = null)
    {
        $settings = $this->pbc_settings ?? [];
        return $settings[$key] ?? $default;
    }
}
