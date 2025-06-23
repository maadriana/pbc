<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $userData['password'] = Hash::make($userData['password']);

        $user = User::create($userData);

        // Assign permissions if provided
        if (isset($userData['permissions'])) {
            $this->updateUserPermissions($user, $userData['permissions']);
        }

        return $user->load(['permissions']);
    }

    public function updateUser(User $user, array $userData): User
    {
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        $user->update($userData);

        // Update permissions if provided
        if (isset($userData['permissions'])) {
            $this->updateUserPermissions($user, $userData['permissions']);
        }

        return $user->load(['permissions']);
    }

    public function deleteUser(User $user): bool
    {
        // Soft delete the user
        return $user->delete();
    }

    public function getUserPermissions(User $user): array
    {
        return $user->permissions->pluck('permission')->toArray();
    }

    public function updateUserPermissions(User $user, array $permissions): void
    {
        // Remove existing permissions
        $user->permissions()->delete();

        // Add new permissions
        foreach ($permissions as $permission) {
            $user->permissions()->create(['permission' => $permission]);
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
}
