@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'System Settings')
@section('page-subtitle', 'Configure system preferences and options')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
}

.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 0;
    position: relative;
    background: none;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border: none;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #007bff;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
}

.card {
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.color-preview {
    width: 40px;
    height: 38px;
    border-radius: 4px;
    border: 1px solid #ced4da;
}

.tab-pane {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

#alertContainer {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1060;
    max-width: 350px;
}
</style>
@endpush

@section('content')
<div class="settings-container">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog me-2"></i>General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
                        <i class="fas fa-building me-2"></i>Company
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                        <i class="fas fa-server me-2"></i>System
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                        <i class="fas fa-palette me-2"></i>Appearance
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="settingsTabContent">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form id="generalSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="app_name">
                                        <i class="fas fa-tag text-muted me-1"></i>Application Name
                                    </label>
                                    <input type="text" class="form-control" id="app_name" name="app_name" placeholder="PBC Checklist Management System">
                                    <small class="form-text text-muted">The name displayed throughout the application</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="timezone">
                                        <i class="fas fa-clock text-muted me-1"></i>Timezone
                                    </label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <option value="Asia/Manila">Asia/Manila (Philippines)</option>
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">America/New_York (EST)</option>
                                        <option value="Europe/London">Europe/London (GMT)</option>
                                        <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_format">
                                        <i class="fas fa-calendar text-muted me-1"></i>Date Format
                                    </label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="Y-m-d">YYYY-MM-DD (2025-01-15)</option>
                                        <option value="m/d/Y">MM/DD/YYYY (01/15/2025)</option>
                                        <option value="d/m/Y">DD/MM/YYYY (15/01/2025)</option>
                                        <option value="F j, Y">Month DD, YYYY (January 15, 2025)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="session_timeout">
                                        <i class="fas fa-hourglass-half text-muted me-1"></i>Session Timeout (minutes)
                                    </label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" min="15" max="480" placeholder="120">
                                    <small class="form-text text-muted">User session timeout duration</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Company Settings -->
                <div class="tab-pane fade" id="company" role="tabpanel">
                    <form id="companySettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name">
                                        <i class="fas fa-building text-muted me-1"></i>Company Name
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Smith & Associates CPA">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_phone">
                                        <i class="fas fa-phone text-muted me-1"></i>Phone Number
                                    </label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone" placeholder="+63 2 8123 4567">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="company_address">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i>Business Address
                            </label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" placeholder="123 Audit Street, Makati City, Metro Manila, Philippines"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_email">
                                        <i class="fas fa-envelope text-muted me-1"></i>Company Email
                                    </label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" placeholder="info@auditfirm.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_website">
                                        <i class="fas fa-globe text-muted me-1"></i>Website
                                    </label>
                                    <input type="url" class="form-control" id="company_website" name="company_website" placeholder="https://www.auditfirm.com">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- System Settings -->
                <div class="tab-pane fade" id="system" role="tabpanel">
                    <form id="systemSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_file_upload_size">
                                        <i class="fas fa-upload text-muted me-1"></i>Max File Upload Size (KB)
                                    </label>
                                    <input type="number" class="form-control" id="max_file_upload_size" name="max_file_upload_size" min="1024" max="51200" placeholder="10240">
                                    <small class="form-text text-muted">Maximum file size for document uploads</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="default_reminder_days">
                                        <i class="fas fa-bell text-muted me-1"></i>Default Reminder Days
                                    </label>
                                    <input type="number" class="form-control" id="default_reminder_days" name="default_reminder_days" min="1" max="30" placeholder="3">
                                    <small class="form-text text-muted">Days before due date to send reminders</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="allowed_file_types">
                                <i class="fas fa-file text-muted me-1"></i>Allowed File Types
                            </label>
                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" placeholder="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt">
                            <small class="form-text text-muted">Comma-separated list of allowed file extensions</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pbc_overdue_threshold">
                                        <i class="fas fa-exclamation-triangle text-muted me-1"></i>Overdue Threshold (days)
                                    </label>
                                    <input type="number" class="form-control" id="pbc_overdue_threshold" name="pbc_overdue_threshold" min="0" max="30" placeholder="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pbc_auto_archive_days">
                                        <i class="fas fa-archive text-muted me-1"></i>Auto Archive Days
                                    </label>
                                    <input type="number" class="form-control" id="pbc_auto_archive_days" name="pbc_auto_archive_days" min="30" max="1095" placeholder="365">
                                    <small class="form-text text-muted">Days after completion to auto-archive projects</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                    <form id="notificationSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notification_email">
                                        <i class="fas fa-envelope text-muted me-1"></i>Notification Email
                                    </label>
                                    <input type="email" class="form-control" id="notification_email" name="notification_email" placeholder="notifications@auditfirm.com">
                                    <small class="form-text text-muted">Email address for system notifications</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-block">
                                        <i class="fas fa-toggle-on text-muted me-1"></i>Notification Options
                                    </label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="auto_reminder_enabled" name="auto_reminder_enabled">
                                        <label class="form-check-label" for="auto_reminder_enabled">Auto Reminder Emails</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="send_email_notifications" name="send_email_notifications">
                                        <label class="form-check-label" for="send_email_notifications">Email Notifications</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="send_sms_notifications" name="send_sms_notifications">
                                        <label class="form-check-label" for="send_sms_notifications">SMS Notifications</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <form id="securitySettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_min_length">
                                        <i class="fas fa-key text-muted me-1"></i>Password Minimum Length
                                    </label>
                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" min="6" max="32" placeholder="8">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_lockout_attempts">
                                        <i class="fas fa-lock text-muted me-1"></i>Account Lockout Attempts
                                    </label>
                                    <input type="number" class="form-control" id="account_lockout_attempts" name="account_lockout_attempts" min="3" max="10" placeholder="5">
                                    <small class="form-text text-muted">Failed login attempts before lockout</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_lockout_duration">
                                        <i class="fas fa-hourglass text-muted me-1"></i>Lockout Duration (minutes)
                                    </label>
                                    <input type="number" class="form-control" id="account_lockout_duration" name="account_lockout_duration" min="5" max="1440" placeholder="30">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-block">
                                        <i class="fas fa-shield-alt text-muted me-1"></i>Password Requirements
                                    </label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="password_require_uppercase" name="password_require_uppercase">
                                        <label class="form-check-label" for="password_require_uppercase">Require Uppercase</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="password_require_numbers" name="password_require_numbers">
                                        <label class="form-check-label" for="password_require_numbers">Require Numbers</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="password_require_special_chars" name="password_require_special_chars">
                                        <label class="form-check-label" for="password_require_special_chars">Require Special Characters</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Appearance Settings -->
                <div class="tab-pane fade" id="appearance" role="tabpanel">
                    <form id="appearanceSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="theme_primary_color">
                                        <i class="fas fa-palette text-muted me-1"></i>Primary Theme Color
                                    </label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="theme_primary_color" name="theme_primary_color" value="#3B82F6">
                                        <span class="input-group-text color-preview" id="primaryColorPreview" style="background-color: #3B82F6;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="theme_secondary_color">
                                        <i class="fas fa-paint-brush text-muted me-1"></i>Secondary Theme Color
                                    </label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="theme_secondary_color" name="theme_secondary_color" value="#10B981">
                                        <span class="input-group-text color-preview" id="secondaryColorPreview" style="background-color: #10B981;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="theme_accent_color">
                                        <i class="fas fa-star text-muted me-1"></i>Accent Color
                                    </label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="theme_accent_color" name="theme_accent_color" value="#F59E0B">
                                        <span class="input-group-text color-preview" id="accentColorPreview" style="background-color: #F59E0B;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-block">
                                        <i class="fas fa-cog text-muted me-1"></i>Interface Options
                                    </label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="dark_mode_enabled" name="dark_mode_enabled">
                                        <label class="form-check-label" for="dark_mode_enabled">Enable Dark Mode</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sidebar_collapsed" name="sidebar_collapsed">
                                        <label class="form-check-label" for="sidebar_collapsed">Collapsed Sidebar by Default</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Save Buttons -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-outline-secondary" id="resetToDefaultsBtn">
                            <i class="fas fa-undo me-1"></i>Reset to Defaults
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary me-2" id="reloadSettingsBtn">
                            <i class="fas fa-sync me-1"></i>Reload
                        </button>
                        <button type="button" class="btn btn-primary" id="saveAllSettingsBtn">
                            <i class="fas fa-save me-1"></i>Save All Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global variables
const currentUserId = {{ auth()->id() }};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let settings = {};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page initializing...');

    // Check if we have required elements
    if (!document.getElementById('settingsTabs')) {
        console.error('Settings tabs not found');
        return;
    }

    loadSettings();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');

    // Color input change events
    const primaryColor = document.getElementById('theme_primary_color');
    const secondaryColor = document.getElementById('theme_secondary_color');
    const accentColor = document.getElementById('theme_accent_color');

    if (primaryColor) {
        primaryColor.addEventListener('change', function() {
            const preview = document.getElementById('primaryColorPreview');
            if (preview) preview.style.backgroundColor = this.value;
        });
    }

    if (secondaryColor) {
        secondaryColor.addEventListener('change', function() {
            const preview = document.getElementById('secondaryColorPreview');
            if (preview) preview.style.backgroundColor = this.value;
        });
    }

    if (accentColor) {
        accentColor.addEventListener('change', function() {
            const preview = document.getElementById('accentColorPreview');
            if (preview) preview.style.backgroundColor = this.value;
        });
    }

    // Add click event listeners to buttons
    const saveBtn = document.getElementById('saveAllSettingsBtn');
    const reloadBtn = document.getElementById('reloadSettingsBtn');
    const resetBtn = document.getElementById('resetToDefaultsBtn');

    if (saveBtn) {
        saveBtn.addEventListener('click', saveAllSettings);
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', loadSettings);
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', resetToDefaults);
    }

    console.log('Event listeners set up successfully');
}

// API helper function using web routes
async function apiCall(url, options = {}) {
    console.log('Making web request to:', url, options);

    if (!csrfToken) {
        console.error('CSRF token not found');
        throw new Error('CSRF token not found');
    }

    const defaultOptions = {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    // For POST requests, send as JSON
    if (options.method === 'POST' && options.body) {
        if (typeof options.body === 'object' && !(options.body instanceof FormData)) {
            defaultOptions.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }
    }

    try {
        const response = await fetch(url, {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Request Error:', errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const result = await response.json();
        console.log('Response data:', result);
        return result;
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
}

// Load all settings
async function loadSettings() {
    console.log('Loading settings...');

    try {
        showAlert('Loading settings...', 'info', 2000);

        // Use web route instead of API route
        const response = await apiCall('/settings/get');

        if (response && response.success) {
            settings = response.data || {};
            populateFormFields();
            showAlert('Settings loaded successfully', 'success', 3000);
        } else {
            throw new Error(response?.message || 'Failed to load settings');
        }

    } catch (error) {
        console.error('Error loading settings:', error);
        showAlert('Failed to load settings: ' + error.message, 'danger');

        // Fallback to defaults
        loadEnvironmentDefaults();
    }
}

// Fallback to load environment defaults if API fails
function loadEnvironmentDefaults() {
    console.log('Loading environment defaults...');

    settings = {
        // General
        app_name: "PBC Checklist Management System",
        timezone: "Asia/Manila",
        date_format: 'Y-m-d',
        session_timeout: 120,

        // Company
        company_name: "Smith & Associates CPA",
        company_address: "123 Audit Street, Makati City, Metro Manila, Philippines",
        company_phone: "+63 2 8123 4567",
        company_email: "info@auditfirm.com",
        company_website: 'https://www.auditfirm.com',

        // System
        max_file_upload_size: 10240,
        default_reminder_days: 3,
        allowed_file_types: "pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt",
        pbc_overdue_threshold: 1,
        pbc_auto_archive_days: 365,

        // Notifications
        notification_email: "notifications@auditfirm.com",
        auto_reminder_enabled: true,
        send_email_notifications: true,
        send_sms_notifications: false,

        // Security
        password_min_length: 8,
        account_lockout_attempts: 5,
        account_lockout_duration: 30,
        password_require_uppercase: true,
        password_require_numbers: true,
        password_require_special_chars: true,

        // Appearance
        theme_primary_color: "#3B82F6",
        theme_secondary_color: "#10B981",
        theme_accent_color: "#F59E0B",
        dark_mode_enabled: true,
        sidebar_collapsed: false
    };

    populateFormFields();
    showAlert('Loaded environment defaults (server unavailable)', 'warning', 3000);
}

// Populate form fields with loaded settings
function populateFormFields() {
    console.log('Populating form fields with settings:', settings);

    Object.keys(settings).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            const value = settings[key];

            if (element.type === 'checkbox') {
                element.checked = value === true || value === 'true' || value === 1;
                console.log(`Set checkbox ${key} to:`, element.checked);
            } else if (element.type === 'color') {
                element.value = value;
                // Update color preview
                const previewId = key.replace('theme_', '') + 'ColorPreview';
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.style.backgroundColor = value;
                }
                console.log(`Set color ${key} to:`, value);
            } else {
                element.value = value;
                console.log(`Set field ${key} to:`, value);
            }
        } else {
            console.warn(`Element with ID ${key} not found`);
        }
    });
}

// Save all settings
async function saveAllSettings() {
    console.log('Saving all settings...');

    try {
        showAlert('Saving settings...', 'info', 2000);

        const allSettings = {};
        const forms = [
            'generalSettingsForm',
            'companySettingsForm',
            'systemSettingsForm',
            'notificationSettingsForm',
            'securitySettingsForm',
            'appearanceSettingsForm'
        ];

        // Collect data from all forms
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                console.log(`Processing form: ${formId}`);

                // Get all inputs in this form
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const key = input.name || input.id;
                    if (key) {
                        if (input.type === 'checkbox') {
                            allSettings[key] = input.checked;
                        } else if (input.value.trim() !== '') {
                            allSettings[key] = input.value;
                        }
                        console.log(`Collected ${key}:`, allSettings[key]);
                    }
                });
            } else {
                console.warn(`Form ${formId} not found`);
            }
        });

        console.log('All collected settings:', allSettings);

        // Use web route instead of API route
        const response = await apiCall('/settings/update', {
            method: 'POST',
            body: allSettings
        });

        if (response && response.success) {
            settings = { ...settings, ...response.data };
            showAlert('Settings saved successfully!', 'success');
            console.log('Settings saved successfully');
        } else {
            throw new Error(response?.message || 'Failed to save settings');
        }

    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('Failed to save settings: ' + error.message, 'danger');
    }
}

// Reset to default values
async function resetToDefaults() {
    console.log('Resetting to defaults...');

    if (!confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
        return;
    }

    try {
        showAlert('Resetting to defaults...', 'info', 2000);

        // Use web route instead of API route
        const response = await apiCall('/settings/reset', {
            method: 'POST'
        });

        if (response && response.success) {
            await loadSettings();
            showAlert('Settings reset to defaults successfully', 'success');
        } else {
            throw new Error(response?.message || 'Failed to reset settings');
        }

    } catch (error) {
        console.error('Error resetting settings:', error);
        showAlert('Failed to reset settings: ' + error.message, 'danger');

        // Fallback to manual reset
        resetToManualDefaults();
    }
}

// Fallback manual reset
function resetToManualDefaults() {
    console.log('Manual reset to defaults...');

    const defaults = {
        app_name: 'PBC Checklist Management System',
        timezone: 'Asia/Manila',
        date_format: 'Y-m-d',
        session_timeout: 120,
        company_name: 'Smith & Associates CPA',
        company_address: '123 Audit Street, Makati City, Metro Manila, Philippines',
        company_phone: '+63 2 8123 4567',
        company_email: 'info@auditfirm.com',
        company_website: 'https://www.auditfirm.com',
        max_file_upload_size: 10240,
        default_reminder_days: 3,
        allowed_file_types: 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
        pbc_overdue_threshold: 1,
        pbc_auto_archive_days: 365,
        notification_email: 'notifications@auditfirm.com',
        auto_reminder_enabled: true,
        send_email_notifications: true,
        send_sms_notifications: false,
        password_min_length: 8,
        account_lockout_attempts: 5,
        account_lockout_duration: 30,
        password_require_uppercase: true,
        password_require_numbers: true,
        password_require_special_chars: true,
        theme_primary_color: '#3B82F6',
        theme_secondary_color: '#10B981',
        theme_accent_color: '#F59E0B',
        dark_mode_enabled: true,
        sidebar_collapsed: false
    };

    settings = defaults;
    populateFormFields();
    showAlert('Settings reset to defaults (manual fallback)', 'warning');
}

// Utility function to show alerts
function showAlert(message, type = 'info', duration = 3000) {
    console.log('Showing alert:', message, type);

    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1060; max-width: 350px;';
        document.body.appendChild(alertContainer);
    }

    const alertId = 'alert_' + Date.now();

    const alertDiv = document.createElement('div');
    alertDiv.id = alertId;
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.cssText = 'margin-bottom: 10px;';

    let iconClass;
    switch(type) {
        case 'success': iconClass = 'check-circle'; break;
        case 'danger': iconClass = 'exclamation-circle'; break;
        case 'warning': iconClass = 'exclamation-triangle'; break;
        default: iconClass = 'info-circle';
    }

    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${iconClass} me-2"></i>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    alertContainer.appendChild(alertDiv);

    if (duration > 0) {
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, duration);
    }
}

// Global error handler
window.addEventListener('error', function(event) {
    console.error('JavaScript Error:', {
        message: event.message,
        source: event.filename,
        line: event.lineno,
        column: event.colno,
        error: event.error
    });
    showAlert('JavaScript error occurred. Check console for details.', 'danger');
});

// Export functions to global scope for debugging
window.settingsFunctions = {
    loadSettings,
    saveAllSettings,
    resetToDefaults,
    populateFormFields,
    showAlert
};
</script>
@endpush
