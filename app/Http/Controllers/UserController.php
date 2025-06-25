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
            // Check permission
            if (!auth()->user()->hasPermission('view_user')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                abort(403, 'Unauthorized access');
            }

            $users = $this->userService->getFilteredUsers($request->all());

            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return $this->paginated($users, 'Users retrieved successfully');
            }

            // For web requests, return the view
            return view('users.index', compact('users'));

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve users: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to retrieve users', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to retrieve users: ' . $e->getMessage()]);
        }
    }

    public function store(CreateUserRequest $request)
    {
        try {
            if (!auth()->user()->hasPermission('create_user')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $user = $this->userService->createUser($request->validated());

            if ($request->expectsJson()) {
                return $this->success($user, 'User created successfully', 201);
            }

            return redirect()->route('users.index')->with('success', 'User created successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to create user: ' . $e->getMessage(), [
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to create user', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(User $user)
    {
        try {
            if (!auth()->user()->hasPermission('view_user')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $user->load(['permissions', 'assignedPbcs', 'requestedPbcs']);
            return $this->success($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user', $e->getMessage(), 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            if (!auth()->user()->hasPermission('edit_user')) {
                if ($request->expectsJson()) {
                    return $this->error('Unauthorized access', null, 403);
                }
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            // Get validated data
            $validatedData = $request->validated();

            // Remove empty password fields to prevent overwriting with empty values
            if (empty($validatedData['password'])) {
                unset($validatedData['password']);
                unset($validatedData['password_confirmation']);
            }

            $updatedUser = $this->userService->updateUser($user, $validatedData);

            if ($request->expectsJson()) {
                return $this->success($updatedUser, 'User updated successfully');
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully');

        } catch (\Exception $e) {
            \Log::error('User update failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $request->validated(),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return $this->error('Failed to update user', $e->getMessage(), 500);
            }

            return back()->withErrors(['error' => 'Failed to update user: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(User $user)
    {
        try {
            if (!auth()->user()->hasPermission('delete_user')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $this->userService->deleteUser($user);
            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user', $e->getMessage(), 500);
        }
    }

    public function permissions(User $user)
    {
        try {
            if (!auth()->user()->hasPermission('view_user')) {
                return $this->error('Unauthorized access', null, 403);
            }

            $permissions = $this->userService->getUserPermissions($user);
            return $this->success($permissions, 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user permissions', $e->getMessage(), 500);
        }
    }

    public function updatePermissions(Request $request, User $user)
    {
        try {
            if (!auth()->user()->hasPermission('manage_permissions')) {
                return $this->error('Unauthorized access', null, 403);
            }

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

    /**
     * Alternative endpoint for user listing (for compatibility)
     */
    public function list(Request $request)
    {
        return $this->index($request);
    }
}
