<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('view_user');

            $users = $this->userService->getFilteredUsers($request->all());
            return $this->paginated($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve users', $e->getMessage(), 500);
        }
    }

    public function store(CreateUserRequest $request)
    {
        try {
            $this->authorize('create_user');

            $user = $this->userService->createUser($request->validated());
            return $this->success($user, 'User created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create user', $e->getMessage(), 500);
        }
    }

    public function show(User $user)
    {
        try {
            $this->authorize('view_user');

            $user->load(['permissions', 'assignedPbcs', 'requestedPbcs']);
            return $this->success($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user', $e->getMessage(), 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->authorize('edit_user');

            $updatedUser = $this->userService->updateUser($user, $request->validated());
            return $this->success($updatedUser, 'User updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update user', $e->getMessage(), 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->authorize('delete_user');

            $this->userService->deleteUser($user);
            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user', $e->getMessage(), 500);
        }
    }

    public function permissions(User $user)
    {
        try {
            $this->authorize('view_user');

            $permissions = $this->userService->getUserPermissions($user);
            return $this->success($permissions, 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user permissions', $e->getMessage(), 500);
        }
    }

    public function updatePermissions(Request $request, User $user)
    {
        try {
            $this->authorize('manage_permissions');

            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string'
            ]);

            $this->userService->updateUserPermissions($user, $request->permissions);
            return $this->success(null, 'User permissions updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update user permissions', $e->getMessage(), 500);
        }
    }
}
