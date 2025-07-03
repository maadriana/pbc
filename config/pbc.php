<?php

return [
    'file_upload' => [
        'max_size' => env('PBC_MAX_FILE_SIZE', 10240), // KB
        'max_files_per_request' => env('PBC_MAX_FILES_PER_REQUEST', 10),
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'ppt', 'pptx', 'txt', 'csv',
            'jpg', 'jpeg', 'png', 'gif'
        ],
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/png',
            'image/gif',
        ],
    ],

    'reminders' => [
        'auto_reminders_enabled' => env('PBC_AUTO_REMINDERS', true),
        'reminder_schedule' => [
            'initial' => 0, // Send immediately when assigned
            'follow_up' => 3, // Days before due date
            'urgent' => 1, // Days before due date
            'final_notice' => -1, // Days after due date (overdue)
        ],
    ],

    'permissions' => [
        'system_admin' => [
            'view_user', 'create_user', 'edit_user', 'delete_user', 'manage_permissions',
            'view_client', 'create_client', 'edit_client', 'delete_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'download_document', 'approve_document', 'reject_document', 'delete_document',
            'send_reminder', 'view_audit_log', 'export_reports', 'manage_settings', 'manage_categories',
            'send_messages', 'view_messages', 'create_conversations', 'receive_notifications'
        ],
        'engagement_partner' => [
            'view_client', 'create_client', 'edit_client', 'delete_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'download_document', 'approve_document', 'reject_document',
            'send_reminder', 'view_audit_log', 'manage_categories',
            'send_messages', 'view_messages', 'create_conversations', 'receive_notifications'
        ],
        'manager' => [
            'view_client', 'create_client', 'edit_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'download_document', 'approve_document', 'reject_document',
            'send_reminder', 'manage_categories',
            'send_messages', 'view_messages', 'create_conversations', 'receive_notifications'
        ],
        'associate' => [
            'view_project', 'create_project', 'edit_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'upload_document', 'download_document', 'approve_document',
            'send_reminder', 'send_messages', 'view_messages', 'receive_notifications'
        ],
        'guest' => [
            'view_pbc_request', 'edit_pbc_request', 'upload_document', 'download_document',
            'view_messages', 'receive_notifications'
        ],
    ],

    'dashboard' => [
        'recent_activity_limit' => 10,
        'overdue_warning_days' => 3,
        'charts_cache_minutes' => 30,
    ],

    'audit' => [
        'log_file_operations' => true,
        'log_permission_changes' => true,
        'cleanup_days' => 2555, // 7 years
        'archive_days' => 365, // 1 year
    ],
];
