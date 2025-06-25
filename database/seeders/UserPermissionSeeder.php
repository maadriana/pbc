<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserPermission;

class UserPermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing permissions
        UserPermission::truncate();

        // System Admin permissions (Level 1) - FULL ACCESS TO EVERYTHING
        $systemAdmins = User::where('role', 'system_admin')->get();
        $adminPermissions = [
            // User Management - ONLY SYSTEM ADMIN
            'view_user', 'create_user', 'edit_user', 'delete_user', 'manage_permissions',

            // Client Management - CRUD All
            'view_client', 'create_client', 'edit_client', 'delete_client',

            // Project Management - CRUD All
            'view_project', 'create_project', 'edit_project', 'delete_project',

            // PBC Requests - CRUD All
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',

            // Documents - Full Access
            'upload_document', 'approve_document', 'delete_document', 'view_document',

            // Communication - Full Access
            'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',

            // Reports - All Data
            'view_analytics', 'export_reports', 'view_audit_log',

            // System - ONLY SYSTEM ADMIN
            'manage_settings', 'view_dashboard'
        ];

        foreach ($systemAdmins as $admin) {
            foreach ($adminPermissions as $permission) {
                UserPermission::create(['user_id' => $admin->id, 'permission' => $permission]);
            }
        }

        // Engagement Partner permissions (Level 2) - Project Leadership
        $engagementPartners = User::where('role', 'engagement_partner')->get();
        $partnerPermissions = [
            // NO User Management

            // Client Management - CRUD All
            'view_client', 'create_client', 'edit_client', 'delete_client',

            // Project Management - CRUD All
            'view_project', 'create_project', 'edit_project', 'delete_project',

            // PBC Requests - CRUD All
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',

            // Documents - Full Access
            'upload_document', 'approve_document', 'delete_document', 'view_document',

            // Communication - Full Access
            'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',

            // Reports - Project Data
            'view_analytics', 'export_reports', 'view_audit_log',

            // Dashboard
            'view_dashboard'
        ];

        foreach ($engagementPartners as $partner) {
            foreach ($partnerPermissions as $permission) {
                UserPermission::create(['user_id' => $partner->id, 'permission' => $permission]);
            }
        }

        // Manager permissions (Level 3) - Project Execution
        $managers = User::where('role', 'manager')->get();
        $managerPermissions = [
            // NO User Management

            // Client Management - Add/Edit Only (NO DELETE)
            'view_client', 'create_client', 'edit_client',

            // Project Management - CRUD All
            'view_project', 'create_project', 'edit_project', 'delete_project',

            // PBC Requests - CRUD All
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',

            // Documents - Full Access
            'upload_document', 'approve_document', 'delete_document', 'view_document',

            // Communication - Full Access
            'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',

            // Reports - Project Data
            'view_analytics', 'export_reports', 'view_audit_log',

            // Dashboard
            'view_dashboard'
        ];

        foreach ($managers as $manager) {
            foreach ($managerPermissions as $permission) {
                UserPermission::create(['user_id' => $manager->id, 'permission' => $permission]);
            }
        }

        // Associate permissions (Level 4) - Task Execution
        $associates = User::where('role', 'associate')->get();
        $associatePermissions = [
            // NO User Management
            // NO Client Management

            // Project Management - Add/Edit Only (NO DELETE)
            'view_project', 'create_project', 'edit_project',

            // PBC Requests - CRUD All
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',

            // Documents - Full Access
            'upload_document', 'approve_document', 'delete_document', 'view_document',

            // Communication - Full Access
            'send_reminder', 'receive_reminder', 'view_messages', 'send_messages',

            // Reports - Task Data
            'view_analytics', 'export_reports', 'view_audit_log',

            // Dashboard
            'view_dashboard'
        ];

        foreach ($associates as $associate) {
            foreach ($associatePermissions as $permission) {
                UserPermission::create(['user_id' => $associate->id, 'permission' => $permission]);
            }
        }

        // Guest permissions (Level 5) - View and Upload Only
        $guests = User::where('role', 'guest')->get();
        $guestPermissions = [
            // NO User Management
            // NO Client Management
            // NO Project Management
            // NO PBC Request Management

            // Documents - Upload Only
            'upload_document', 'view_document',

            // Communication - Limited Access
            'receive_reminder', 'view_messages',

            // Reports - Limited View Only
            'view_analytics',

            // Dashboard - Limited View
            'view_dashboard'
        ];

        foreach ($guests as $guest) {
            foreach ($guestPermissions as $permission) {
                UserPermission::create(['user_id' => $guest->id, 'permission' => $permission]);
            }
        }

        $this->command->info('User permissions seeded successfully according to the access matrix!');

        // Output permission summary
        $this->command->info('');
        $this->command->info('Permission Summary:');
        $this->command->info('System Admin: ' . count($adminPermissions) . ' permissions');
        $this->command->info('Engagement Partner: ' . count($partnerPermissions) . ' permissions');
        $this->command->info('Manager: ' . count($managerPermissions) . ' permissions');
        $this->command->info('Associate: ' . count($associatePermissions) . ' permissions');
        $this->command->info('Guest: ' . count($guestPermissions) . ' permissions');
    }
}
