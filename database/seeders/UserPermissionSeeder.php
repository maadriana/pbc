<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserPermission;

class UserPermissionSeeder extends Seeder
{
    public function run()
    {
        // System Admin permissions
        $systemAdmin = User::where('role', 'system_admin')->first();
        $adminPermissions = [
            'create_user', 'edit_user', 'delete_user', 'view_user',
            'create_client', 'edit_client', 'delete_client', 'view_client',
            'create_project', 'edit_project', 'delete_project', 'view_project',
            'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request', 'view_pbc_request',
            'approve_document', 'reject_document', 'download_document',
            'send_reminder', 'view_audit_log', 'manage_settings',
            'view_dashboard', 'export_reports', 'manage_templates'
        ];

        foreach ($adminPermissions as $permission) {
            UserPermission::create([
                'user_id' => $systemAdmin->id,
                'permission' => $permission,
            ]);
        }

        // Engagement Partner permissions
        $engagementPartners = User::where('role', 'engagement_partner')->get();
        $partnerPermissions = [
            'create_client', 'edit_client', 'view_client',
            'create_project', 'edit_project', 'view_project',
            'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
            'approve_document', 'reject_document', 'download_document',
            'send_reminder', 'view_dashboard', 'export_reports'
        ];

        foreach ($engagementPartners as $partner) {
            foreach ($partnerPermissions as $permission) {
                UserPermission::create([
                    'user_id' => $partner->id,
                    'permission' => $permission,
                ]);
            }
        }

        // Manager permissions
        $managers = User::where('role', 'manager')->get();
        $managerPermissions = [
            'edit_client', 'view_client',
            'edit_project', 'view_project',
            'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
            'approve_document', 'reject_document', 'download_document',
            'send_reminder', 'view_dashboard'
        ];

        foreach ($managers as $manager) {
            foreach ($managerPermissions as $permission) {
                UserPermission::create([
                    'user_id' => $manager->id,
                    'permission' => $permission,
                ]);
            }
        }

        // Associate permissions
        $associates = User::where('role', 'associate')->get();
        $associatePermissions = [
            'view_client', 'view_project',
            'create_pbc_request', 'edit_pbc_request', 'view_pbc_request',
            'download_document', 'send_reminder'
        ];

        foreach ($associates as $associate) {
            foreach ($associatePermissions as $permission) {
                UserPermission::create([
                    'user_id' => $associate->id,
                    'permission' => $permission,
                ]);
            }
        }

        // Guest (Client) permissions
        $guests = User::where('role', 'guest')->get();
        $guestPermissions = [
            'view_pbc_request', 'upload_document', 'download_document'
        ];

        foreach ($guests as $guest) {
            foreach ($guestPermissions as $permission) {
                UserPermission::create([
                    'user_id' => $guest->id,
                    'permission' => $permission,
                ]);
            }
        }
    }
}
