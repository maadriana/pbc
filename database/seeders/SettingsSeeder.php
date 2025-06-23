<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => 'PBC Checklist Management System',
                'type' => 'string',
                'description' => 'Application name displayed in the system',
                'is_public' => true,
            ],
            [
                'key' => 'company_name',
                'value' => 'Smith & Associates CPA',
                'type' => 'string',
                'description' => 'Audit firm company name',
                'is_public' => true,
            ],
            [
                'key' => 'company_address',
                'value' => '123 Audit Street, Makati City, Metro Manila, Philippines',
                'type' => 'string',
                'description' => 'Company business address',
                'is_public' => true,
            ],
            [
                'key' => 'default_reminder_days',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Default number of days before due date to send reminders',
                'is_public' => false,
            ],
            [
                'key' => 'max_file_upload_size',
                'value' => '10240',
                'type' => 'integer',
                'description' => 'Maximum file upload size in KB',
                'is_public' => false,
            ],
            [
                'key' => 'allowed_file_types',
                'value' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
                'type' => 'string',
                'description' => 'Allowed file extensions for upload',
                'is_public' => false,
            ],
            [
                'key' => 'auto_reminder_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable automatic reminder emails',
                'is_public' => false,
            ],
            [
                'key' => 'notification_email',
                'value' => 'notifications@auditfirm.com',
                'type' => 'string',
                'description' => 'Email address for system notifications',
                'is_public' => false,
            ],
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'description' => 'Session timeout in minutes',
                'is_public' => false,
            ],
            [
                'key' => 'theme_color',
                'value' => '#3B82F6',
                'type' => 'string',
                'description' => 'Primary theme color',
                'is_public' => true,
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'description' => 'Default date format for the system',
                'is_public' => true,
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Manila',
                'type' => 'string',
                'description' => 'System timezone',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
