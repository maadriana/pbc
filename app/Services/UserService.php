<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function getFilteredUsers(array $filters): LengthAwarePaginator
    {
        $query = User::with(['permissions'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('entity', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($filters['access_level'] ?? null, function ($query, $level) {
                $query->where('access_level', $level);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function createUser(array $userData): User
    {
        DB::beginTransaction();

        try {
            // Hash password
            $userData['password'] = Hash::make($userData['password']);

            // Create user
            $user = User::create($userData);

            // Assign role-based permissions automatically
            $this->assignRoleBasedPermissions($user);

            // Assign additional permissions if provided
            if (isset($userData['permissions'])) {
                $this->updateUserPermissions($user, $userData['permissions']);
            }

            DB::commit();
            return $user->load(['permissions']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateUser(User $user, array $userData): User
    {
        DB::beginTransaction();

        try {
            // Hash password if provided
            if (isset($userData['password']) && !empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                // Remove password fields if empty to prevent overwriting
                unset($userData['password']);
                unset($userData['password_confirmation']);
            }

            // Update user data
            $user->update($userData);

            // If role changed, reassign role-based permissions
            if (isset($userData['role']) && $userData['role'] !== $user->getOriginal('role')) {
                $this->assignRoleBasedPermissions($user);
            }

            // Update additional permissions if provided
            if (isset($userData['permissions'])) {
                $this->updateUserPermissions($user, $userData['permissions']);
            }

            DB::commit();
            return $user->fresh()->load(['permissions']);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('UserService updateUser failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'userData' => $userData,
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteUser(User $user): bool
    {
        DB::beginTransaction();

        try {
            // Remove permissions first
            $user->permissions()->delete();

            // Soft delete the user
            $result = $user->delete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getUserPermissions(User $user): array
    {
        return $user->permissions->pluck('permission')->toArray();
    }

    public function updateUserPermissions(User $user, array $permissions): void
    {
        DB::beginTransaction();

        try {
            // Remove existing permissions
            $user->permissions()->delete();

            // Add new permissions
            foreach ($permissions as $permission) {
                $user->permissions()->create(['permission' => $permission]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);
        return $user;
    }

    public function deactivateUser(User $user): User
    {
        $user->update(['is_active' => false]);
        return $user;
    }

    /**
     * Assign role-based permissions automatically based on the access matrix
     */
    private function assignRoleBasedPermissions(User $user): void
    {
        // Remove existing permissions
        $user->permissions()->delete();

        // Define role-based permissions according to access matrix
        $rolePermissions = [
            'system_admin' => [
                'view_user', 'create_user', 'edit_user', 'delete_user', 'manage_permissions',
                'view_client', 'create_client', 'edit_client', 'delete_client',
                'view_project', 'create_project', 'edit_project', 'delete_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'delete_document', 'view_document',
                'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',
                'view_analytics', 'export_reports', 'view_audit_log',
                'manage_settings', 'view_dashboard'
            ],
            'engagement_partner' => [
                'view_client', 'create_client', 'edit_client', 'delete_client',
                'view_project', 'create_project', 'edit_project', 'delete_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'delete_document', 'view_document',
                'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',
                'view_analytics', 'export_reports', 'view_audit_log',
                'view_dashboard'
            ],
            'manager' => [
                'view_client', 'create_client', 'edit_client',
                'view_project', 'create_project', 'edit_project', 'delete_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'delete_document', 'view_document',
                'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',
                'view_analytics', 'export_reports', 'view_audit_log',
                'view_dashboard'
            ],
            'associate' => [
                'view_project', 'create_project', 'edit_project',
                'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
                'upload_document', 'approve_document', 'delete_document', 'view_document',
                'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',
                'view_analytics', 'export_reports', 'view_audit_log',
                'view_dashboard'
            ],
            'guest' => [
                'upload_document', 'view_document',
                'receive_reminder', 'view_messages',
                'view_analytics',
                'view_dashboard'
            ]
        ];

        $permissions = $rolePermissions[$user->role] ?? [];

        foreach ($permissions as $permission) {
            $user->permissions()->create(['permission' => $permission]);
        }
    }
}
