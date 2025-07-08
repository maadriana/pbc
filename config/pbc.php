<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'file_upload' => [
        'max_size' => 10240, // Maximum file size in KB (10MB)
        'max_files_per_request' => 10, // Maximum files per upload request
        'allowed_types' => [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'jpg',
            'jpeg',
            'png',
            'txt',
            'csv'
        ],
        'disk' => 'pbc-documents', // Storage disk name
        'path' => 'pbc-documents', // Base path within disk
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => 'pbc-documents',
        'visibility' => 'private', // Files are private by default
        'organize_by' => 'project', // 'project', 'client', or 'date'
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    */
    'progress' => [
        'auto_update' => true, // Automatically update progress when items change
        'include_optional_items' => false, // Include optional items in progress calculation
        'completion_threshold' => 100, // Percentage required to mark as completed
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['mail', 'database'], // Available: mail, database, slack
        'auto_notify' => [
            'file_uploaded' => true,
            'file_approved' => true,
            'file_rejected' => true,
            'request_completed' => true,
            'item_overdue' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Settings
    |--------------------------------------------------------------------------
    */
    'reminders' => [
        'auto_reminders_enabled' => true,
        'reminder_schedule' => [
            'follow_up' => 3,  // Days before due date
            'urgent' => 1,     // Days before due date
        ],
        'default_method' => 'email', // email, sms, system
        'max_reminders_per_item' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI/UX Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'items_per_page' => 25,
        'show_file_icons' => true,
        'enable_file_preview' => true,
        'enable_drag_drop' => true,
        'show_progress_bars' => true,
        'auto_refresh_interval' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'scan_uploads' => false, // Enable virus scanning (requires additional setup)
        'hash_filenames' => true, // Use UUID-based filenames
        'log_downloads' => true, // Log all file downloads
        'require_authentication' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit and Compliance
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'log_all_actions' => true,
        'retain_logs_days' => 2555, // 7 years (Philippine compliance)
        'include_ip_address' => true,
        'include_user_agent' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => true,
        'cache_duration' => 3600, // 1 hour in seconds
        'eager_load_relationships' => true,
        'optimize_file_serving' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enable_comments' => true,
        'enable_reminders' => true,
        'enable_file_versioning' => true,
        'enable_bulk_operations' => true,
        'enable_templates' => true,
        'enable_categories' => true,
        'enable_export' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Templates
    |--------------------------------------------------------------------------
    */
    'default_templates' => [
        'audit' => 'at_700',
        'tax' => 'tax_100',
        'accounting' => 'review_200',
        'special_engagement' => 'custom_001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Definitions
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'request' => [
            'draft' => 'Draft - Not yet active',
            'active' => 'Active - In progress',
            'completed' => 'Completed - All items finished',
            'cancelled' => 'Cancelled - No longer needed',
        ],
        'item' => [
            'pending' => 'Pending - Awaiting submission',
            'submitted' => 'Submitted - Under review',
            'under_review' => 'Under Review - Being evaluated',
            'accepted' => 'Accepted - Approved by reviewer',
            'rejected' => 'Rejected - Needs revision',
            'overdue' => 'Overdue - Past due date',
        ],
        'submission' => [
            'pending' => 'Pending - Awaiting review',
            'under_review' => 'Under Review - Being evaluated',
            'accepted' => 'Accepted - Approved',
            'rejected' => 'Rejected - Not approved',
        ],
    ],
];
