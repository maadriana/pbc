<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $credentials): array
    {
        $email = $credentials['email'];
        $password = $credentials['password'];
        $remember = $credentials['remember'] ?? false;

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->logActivity('login_failed', null, 'User not found: ' . $email);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!$user->is_active) {
            $this->logActivity('login_failed', $user->id, 'Inactive user attempted login');
            return ['success' => false, 'message' => 'Account is inactive'];
        }

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $user = Auth::user();
            $user->load(['permissions']);

            $token = $user->createToken('auth_token')->plainTextToken;

            $this->logActivity('login', $user->id, 'User logged in successfully');

            return [
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'permissions' => $user->permissions->pluck('permission')->toArray(),
                ]
            ];
        }

        $this->logActivity('login_failed', $user->id, 'Invalid password');
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function register(array $userData): User
    {
        $userData['password'] = Hash::make($userData['password']);

        $user = User::create($userData);

        $this->assignDefaultPermissions($user);

        $this->logActivity('register', $user->id, 'User registered successfully');

        return $user->load(['permissions']);
    }

    private function assignDefaultPermissions(User $user): void
    {
        $defaultPermissions = [];

        switch ($user->role) {
            case 'system_admin':
                $defaultPermissions = [
                    'create_user', 'edit_user', 'delete_user', 'view_user',
                    'create_client', 'edit_client', 'delete_client', 'view_client',
                    'create_project', 'edit_project', 'delete_project', 'view_project',
                    'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request', 'view_pbc_request',
                    'approve_document', 'reject_document', 'download_document',
                    'send_reminder', 'view_audit_log', 'manage_settings',
                    'view_dashboard', 'export_reports', 'manage_templates'
                ];
                break;
            case 'engagement_partner':
                $defaultPermissions = [
                    'create_client', 'edit_client', 'view_client',
                    'create_project', 'edit_project', 'view_project',
                    'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
                    'approve_document', 'reject_document', 'download_document',
                    'send_reminder', 'view_dashboard', 'export_reports'
                ];
                break;
            case 'manager':
                $defaultPermissions = [
                    'edit_client', 'view_client',
                    'edit_project', 'view_project',
                    'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
                    'approve_document', 'reject_document', 'download_document',
                    'send_reminder', 'view_dashboard'
                ];
                break;
            case 'associate':
                $defaultPermissions = [
                    'view_client', 'view_project',
                    'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
                    'download_document', 'send_reminder'
                ];
                break;
            case 'guest':
                $defaultPermissions = [
                    'view_pbc_request', 'upload_document', 'download_document'
                ];
                break;
        }

        foreach ($defaultPermissions as $permission) {
            \App\Models\UserPermission::create([
                'user_id' => $user->id,
                'permission' => $permission
            ]);
        }
    }

    private function logActivity(string $action, ?int $userId, string $description): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => User::class,
                'model_id' => $userId,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }
}
