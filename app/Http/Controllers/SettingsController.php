<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class SettingsController extends BaseController
{
    /**
     * Display the settings page
     */
    public function index()
    {
        // Check permission
        if (!auth()->user()->hasPermission('manage_settings')) {
            abort(403, 'You do not have permission to access settings.');
        }

        return view('settings');
    }

    /**
     * Get all settings (Web endpoint)
     */
    public function getSettings(): JsonResponse
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('manage_settings')) {
                return $this->error('You do not have permission to view settings', null, 403);
            }

            // Get all settings from database
            $settings = Setting::all()->pluck('casted_value', 'key')->toArray();

            // Add some default values if they don't exist
            $defaultSettings = [
                'app_name' => config('app.name', 'PBC Checklist Management System'),
                'timezone' => config('app.timezone', 'Asia/Manila'),
                'date_format' => 'Y-m-d',
                'session_timeout' => 120,
                'company_name' => 'Smith & Associates CPA',
                'company_address' => '123 Audit Street, Makati City, Metro Manila, Philippines',
                'company_phone' => '+63 2 8123 4567',
                'company_email' => 'info@auditfirm.com',
                'company_website' => 'https://www.auditfirm.com',
                'max_file_upload_size' => 10240,
                'default_reminder_days' => 3,
                'allowed_file_types' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
                'notification_email' => 'notifications@auditfirm.com',
                'auto_reminder_enabled' => true,
                'send_email_notifications' => true,
                'send_sms_notifications' => false,
                'password_min_length' => 8,
                'account_lockout_attempts' => 5,
                'account_lockout_duration' => 30,
                'password_require_uppercase' => true,
                'password_require_numbers' => true,
                'password_require_special_chars' => true,
                'theme_primary_color' => '#3B82F6',
                'theme_secondary_color' => '#10B981',
                'theme_accent_color' => '#F59E0B',
                'dark_mode_enabled' => true,
                'sidebar_collapsed' => false,
                'pbc_overdue_threshold' => 1,
                'pbc_auto_archive_days' => 365,
            ];

            // Merge defaults with database settings
            $allSettings = array_merge($defaultSettings, $settings);

            return $this->success($allSettings, 'Settings retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to get settings: ' . $e->getMessage());
            return $this->error('Failed to retrieve settings', $e->getMessage(), 500);
        }
    }

    /**
     * Update settings (Web endpoint)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('manage_settings')) {
                return $this->error('You do not have permission to update settings', null, 403);
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                // General
                'app_name' => 'nullable|string|max:255',
                'timezone' => 'nullable|string|max:50',
                'date_format' => 'nullable|string|max:20',
                'session_timeout' => 'nullable|integer|min:15|max:480',

                // Company
                'company_name' => 'nullable|string|max:255',
                'company_address' => 'nullable|string|max:500',
                'company_phone' => 'nullable|string|max:50',
                'company_email' => 'nullable|email|max:255',
                'company_website' => 'nullable|url|max:255',

                // System
                'max_file_upload_size' => 'nullable|integer|min:1024|max:51200',
                'default_reminder_days' => 'nullable|integer|min:1|max:30',
                'allowed_file_types' => 'nullable|string|max:500',
                'pbc_overdue_threshold' => 'nullable|integer|min:0|max:30',
                'pbc_auto_archive_days' => 'nullable|integer|min:30|max:1095',

                // Notifications
                'notification_email' => 'nullable|email|max:255',
                'auto_reminder_enabled' => 'nullable|boolean',
                'send_email_notifications' => 'nullable|boolean',
                'send_sms_notifications' => 'nullable|boolean',

                // Security
                'password_min_length' => 'nullable|integer|min:6|max:32',
                'account_lockout_attempts' => 'nullable|integer|min:3|max:10',
                'account_lockout_duration' => 'nullable|integer|min:5|max:1440',
                'password_require_uppercase' => 'nullable|boolean',
                'password_require_numbers' => 'nullable|boolean',
                'password_require_special_chars' => 'nullable|boolean',

                // Appearance
                'theme_primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'theme_secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'theme_accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'dark_mode_enabled' => 'nullable|boolean',
                'sidebar_collapsed' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $updatedSettings = [];
            $validatedData = $validator->validated();

            // Process each setting
            foreach ($validatedData as $key => $value) {
                if ($value !== null) {
                    // Determine the type
                    $type = $this->getSettingType($key, $value);

                    // Update or create setting
                    $setting = Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => $this->convertValueForStorage($value, $type),
                            'type' => $type,
                            'description' => $this->getSettingDescription($key),
                            'is_public' => $this->isPublicSetting($key)
                        ]
                    );

                    $updatedSettings[$key] = $setting->casted_value;
                }
            }

            // Clear cache safely without tags
            $this->clearSettingsCache();

            // Log the action
            \Log::info('Settings updated', [
                'user_id' => auth()->id(),
                'updated_settings' => array_keys($updatedSettings),
                'ip' => request()->ip()
            ]);

            return $this->success($updatedSettings, 'Settings updated successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to update settings: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return $this->error('Failed to update settings', $e->getMessage(), 500);
        }
    }

    /**
     * Reset settings to default values (Web endpoint)
     */
    public function resetToDefaults(): JsonResponse
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('manage_settings')) {
                return $this->error('You do not have permission to reset settings', null, 403);
            }

            // Delete all existing settings
            Setting::truncate();

            // Create default settings
            $defaults = [
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
                    'key' => 'company_phone',
                    'value' => '+63 2 8123 4567',
                    'type' => 'string',
                    'description' => 'Company phone number',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_email',
                    'value' => 'info@auditfirm.com',
                    'type' => 'string',
                    'description' => 'Company email address',
                    'is_public' => true,
                ],
                [
                    'key' => 'company_website',
                    'value' => 'https://www.auditfirm.com',
                    'type' => 'string',
                    'description' => 'Company website URL',
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
                    'key' => 'pbc_overdue_threshold',
                    'value' => '1',
                    'type' => 'integer',
                    'description' => 'Days after due date before marking as overdue',
                    'is_public' => false,
                ],
                [
                    'key' => 'pbc_auto_archive_days',
                    'value' => '365',
                    'type' => 'integer',
                    'description' => 'Days after completion to auto-archive projects',
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
                    'key' => 'send_email_notifications',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Enable email notifications',
                    'is_public' => false,
                ],
                [
                    'key' => 'send_sms_notifications',
                    'value' => 'false',
                    'type' => 'boolean',
                    'description' => 'Enable SMS notifications',
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
                    'key' => 'password_min_length',
                    'value' => '8',
                    'type' => 'integer',
                    'description' => 'Minimum password length',
                    'is_public' => false,
                ],
                [
                    'key' => 'account_lockout_attempts',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Failed login attempts before lockout',
                    'is_public' => false,
                ],
                [
                    'key' => 'account_lockout_duration',
                    'value' => '30',
                    'type' => 'integer',
                    'description' => 'Account lockout duration in minutes',
                    'is_public' => false,
                ],
                [
                    'key' => 'password_require_uppercase',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require uppercase letters in passwords',
                    'is_public' => false,
                ],
                [
                    'key' => 'password_require_numbers',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require numbers in passwords',
                    'is_public' => false,
                ],
                [
                    'key' => 'password_require_special_chars',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require special characters in passwords',
                    'is_public' => false,
                ],
                [
                    'key' => 'theme_primary_color',
                    'value' => '#3B82F6',
                    'type' => 'string',
                    'description' => 'Primary theme color',
                    'is_public' => true,
                ],
                [
                    'key' => 'theme_secondary_color',
                    'value' => '#10B981',
                    'type' => 'string',
                    'description' => 'Secondary theme color',
                    'is_public' => true,
                ],
                [
                    'key' => 'theme_accent_color',
                    'value' => '#F59E0B',
                    'type' => 'string',
                    'description' => 'Accent theme color',
                    'is_public' => true,
                ],
                [
                    'key' => 'dark_mode_enabled',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Enable dark mode by default',
                    'is_public' => true,
                ],
                [
                    'key' => 'sidebar_collapsed',
                    'value' => 'false',
                    'type' => 'boolean',
                    'description' => 'Collapse sidebar by default',
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

            foreach ($defaults as $setting) {
                Setting::create($setting);
            }

            // Clear cache safely without tags
            $this->clearSettingsCache();

            // Log the action
            \Log::warning('Settings reset to defaults', [
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);

            return $this->success(null, 'Settings reset to defaults successfully');

        } catch (\Exception $e) {
            \Log::error('Failed to reset settings: ' . $e->getMessage());
            return $this->error('Failed to reset settings', $e->getMessage(), 500);
        }
    }

    /**
     * Clear settings cache safely without using tags
     */
    private function clearSettingsCache(): void
    {
        try {
            // Clear specific cache keys that might be used for settings
            Cache::forget('app_settings');
            Cache::forget('settings');
            Cache::forget('system_settings');

            // If you're using pattern-based cache keys, you might need to clear them individually
            // or use a different approach based on your cache driver

        } catch (\Exception $e) {
            // If cache clearing fails, log it but don't fail the entire operation
            \Log::warning('Failed to clear settings cache: ' . $e->getMessage());
        }
    }

    /**
     * Determine the setting type based on key and value
     */
    private function getSettingType($key, $value): string
    {
        // Boolean settings
        $booleanKeys = [
            'auto_reminder_enabled', 'send_email_notifications', 'send_sms_notifications',
            'password_require_uppercase', 'password_require_numbers', 'password_require_special_chars',
            'dark_mode_enabled', 'sidebar_collapsed'
        ];

        if (in_array($key, $booleanKeys)) {
            return 'boolean';
        }

        // Integer settings
        $integerKeys = [
            'session_timeout', 'max_file_upload_size', 'default_reminder_days',
            'password_min_length', 'account_lockout_attempts', 'account_lockout_duration',
            'pbc_overdue_threshold', 'pbc_auto_archive_days'
        ];

        if (in_array($key, $integerKeys)) {
            return 'integer';
        }

        // Default to string
        return 'string';
    }

    /**
     * Convert value for storage based on type
     */
    private function convertValueForStorage($value, $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return (string) (int) $value;
            case 'json':
            case 'array':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Get setting description
     */
    private function getSettingDescription($key): string
    {
        $descriptions = [
            'app_name' => 'Application name displayed in the system',
            'company_name' => 'Audit firm company name',
            'company_address' => 'Company business address',
            'company_phone' => 'Company phone number',
            'company_email' => 'Company email address',
            'company_website' => 'Company website URL',
            'default_reminder_days' => 'Default number of days before due date to send reminders',
            'max_file_upload_size' => 'Maximum file upload size in KB',
            'allowed_file_types' => 'Allowed file extensions for upload',
            'auto_reminder_enabled' => 'Enable automatic reminder emails',
            'notification_email' => 'Email address for system notifications',
            'session_timeout' => 'Session timeout in minutes',
            'theme_primary_color' => 'Primary theme color',
            'theme_secondary_color' => 'Secondary theme color',
            'theme_accent_color' => 'Accent theme color',
            'date_format' => 'Default date format for the system',
            'timezone' => 'System timezone',
            'pbc_overdue_threshold' => 'Days after due date before marking as overdue',
            'pbc_auto_archive_days' => 'Days after completion to auto-archive projects',
            'password_min_length' => 'Minimum password length',
            'account_lockout_attempts' => 'Failed login attempts before lockout',
            'account_lockout_duration' => 'Account lockout duration in minutes',
            'password_require_uppercase' => 'Require uppercase letters in passwords',
            'password_require_numbers' => 'Require numbers in passwords',
            'password_require_special_chars' => 'Require special characters in passwords',
            'dark_mode_enabled' => 'Enable dark mode by default',
            'sidebar_collapsed' => 'Collapse sidebar by default',
            'send_email_notifications' => 'Enable email notifications',
            'send_sms_notifications' => 'Enable SMS notifications',
        ];

        return $descriptions[$key] ?? 'System setting';
    }

    /**
     * Check if setting is public
     */
    private function isPublicSetting($key): bool
    {
        $publicKeys = [
            'app_name', 'company_name', 'company_address', 'company_phone', 'company_email', 'company_website',
            'theme_primary_color', 'theme_secondary_color', 'theme_accent_color', 'date_format', 'timezone',
            'dark_mode_enabled', 'sidebar_collapsed'
        ];

        return in_array($key, $publicKeys);
    }
}
