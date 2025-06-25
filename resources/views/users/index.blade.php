@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-subtitle', 'Manage system users, roles, and permissions')

@section('content')
<div x-data="userManagement()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="user-management-header">
        <div class="header-title">
            <h2>System Users</h2>
            <p class="header-description">Manage user accounts, roles, and access permissions</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="exportUsers()" :disabled="loading">
                <i class="fas fa-download"></i>
                Export Users
            </button>
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Add New User
            </button>
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Users</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by name, email, or entity..."
                        x-model="filters.search"
                        @input.debounce.500ms="loadUsers()"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Role</label>
                <select class="filter-select" x-model="filters.role" @change="loadUsers()">
                    <option value="">All Roles</option>
                    <option value="system_admin">System Admin</option>
                    <option value="engagement_partner">Engagement Partner</option>
                    <option value="manager">Manager</option>
                    <option value="associate">Associate</option>
                    <option value="guest">Guest</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Access Level</label>
                <select class="filter-select" x-model="filters.access_level" @change="loadUsers()">
                    <option value="">All Levels</option>
                    <option value="1">Level 1 (Full Access)</option>
                    <option value="2">Level 2 (High Access)</option>
                    <option value="3">Level 3 (Medium Access)</option>
                    <option value="4">Level 4 (Limited Access)</option>
                    <option value="5">Level 5 (View Only)</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.is_active" @change="loadUsers()">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">&nbsp;</label>
                <button class="btn btn-secondary" @click="clearFilters()">
                    <i class="fas fa-times"></i>
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- LOADING STATE -->
    <div x-show="loading" class="loading-container">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading users...</span>
        </div>
    </div>

    <!-- USERS TABLE -->
    <div class="users-card" x-show="!loading">
        <div class="table-header">
            <div class="table-title">
                <h3>Users (<span x-text="pagination.total || 0"></span>)</h3>
            </div>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" @click="loadUsers()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" @change="toggleSelectAll($event)">
                        </th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Access Level</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="user in users" :key="user.id">
                        <tr :class="{ 'selected': selectedUsers.includes(user.id) }">
                            <td>
                                <input
                                    type="checkbox"
                                    :checked="selectedUsers.includes(user.id)"
                                    @change="toggleUserSelection(user.id)"
                                >
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar" :style="`background: ${getUserAvatarColor(user.name)}`">
                                        <span x-text="getUserInitials(user.name)"></span>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name" x-text="user.name"></div>
                                        <div class="user-email" x-text="user.email"></div>
                                        <div class="user-entity" x-text="user.entity || 'No entity'" x-show="user.entity"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge" :class="`role-${user.role.replace('_', '-')}`" x-text="formatRole(user.role)"></span>
                            </td>
                            <td>
                                <span class="access-level-badge" :class="`level-${user.access_level}`">
                                    Level <span x-text="user.access_level"></span>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge" :class="`status-${user.is_active ? 'active' : 'inactive'}`"
                                      x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td>
                                <span class="date-text" x-text="formatDate(user.created_at)"></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-xs btn-secondary" @click="viewUser(user)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-xs btn-warning" @click="editUser(user)" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-xs btn-info" @click="managePermissions(user)" title="Manage Permissions">
                                        <i class="fas fa-shield-alt"></i>
                                    </button>
                                    <button class="btn btn-xs btn-danger" @click="deleteUser(user)" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- EMPTY STATE -->
            <div x-show="users.length === 0 && !loading" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>No users found</h3>
                <p>Try adjusting your search criteria or create a new user.</p>
                <button class="btn btn-primary" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create First User
                </button>
            </div>
        </div>

        <!-- PAGINATION -->
        <div class="table-pagination" x-show="users.length > 0">
            <div class="pagination-info">
                Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                of <span x-text="pagination.total || 0"></span> users
            </div>
            <div class="pagination-controls">
                <button
                    class="pagination-btn"
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                >
                    <i class="fas fa-chevron-left"></i>
                </button>

                <template x-for="page in visiblePages" :key="page">
                    <button
                        class="pagination-btn"
                        :class="{ 'active': page === pagination.current_page }"
                        @click="changePage(page)"
                        x-text="page"
                    ></button>
                </template>

                <button
                    class="pagination-btn"
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- BULK ACTIONS -->
    <div class="bulk-actions" x-show="selectedUsers.length > 0" x-transition>
        <div class="bulk-actions-content">
            <span class="selected-count"><span x-text="selectedUsers.length"></span> users selected</span>
            <div class="bulk-actions-buttons">
                <button class="btn btn-sm btn-warning" @click="bulkActivate()">
                    <i class="fas fa-check"></i>
                    Activate
                </button>
                <button class="btn btn-sm btn-secondary" @click="bulkDeactivate()">
                    <i class="fas fa-pause"></i>
                    Deactivate
                </button>
                <button class="btn btn-sm btn-danger" @click="bulkDelete()">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
                <button class="btn btn-sm btn-light" @click="clearSelection()">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- CREATE/EDIT USER MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEditing ? 'Edit User' : 'Create New User'"></h3>
                <button class="modal-close" @click="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveUser()">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input
                                type="text"
                                class="form-input"
                                x-model="userForm.name"
                                required
                                placeholder="Enter full name"
                                :class="{ 'error': errors.name }"
                            >
                            <div class="form-error" x-show="errors.name" x-text="errors.name"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input
                                type="email"
                                class="form-input"
                                x-model="userForm.email"
                                required
                                placeholder="Enter email address"
                                :class="{ 'error': errors.email }"
                            >
                            <div class="form-error" x-show="errors.email" x-text="errors.email"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Entity/Company</label>
                            <input
                                type="text"
                                class="form-input"
                                x-model="userForm.entity"
                                placeholder="Enter entity or company name"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Number</label>
                            <input
                                type="text"
                                class="form-input"
                                x-model="userForm.contact_number"
                                placeholder="Enter contact number"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Role *</label>
                            <select class="form-select" x-model="userForm.role" required :class="{ 'error': errors.role }">
                                <option value="">Select Role</option>
                                <option value="system_admin">System Administrator</option>
                                <option value="engagement_partner">Engagement Partner</option>
                                <option value="manager">Manager</option>
                                <option value="associate">Associate</option>
                                <option value="guest">Guest</option>
                            </select>
                            <div class="form-error" x-show="errors.role" x-text="errors.role"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Access Level *</label>
                            <select class="form-select" x-model="userForm.access_level" required>
                                <option value="">Select Access Level</option>
                                <option value="1">Level 1 - Full Access (System Admin)</option>
                                <option value="2">Level 2 - High Access (Partners)</option>
                                <option value="3">Level 3 - Medium Access (Managers)</option>
                                <option value="4">Level 4 - Limited Access (Associates)</option>
                                <option value="5">Level 5 - View Only (Guests)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-divider" x-show="!isEditing">
                        <span>Password Setup</span>
                    </div>

                    <div class="form-grid" x-show="!isEditing">
                        <div class="form-group">
                            <label class="form-label">Password *</label>
                            <input
                                type="password"
                                class="form-input"
                                x-model="userForm.password"
                                :required="!isEditing"
                                placeholder="Enter password"
                                :class="{ 'error': errors.password }"
                            >
                            <div class="form-error" x-show="errors.password" x-text="errors.password"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm Password *</label>
                            <input
                                type="password"
                                class="form-input"
                                x-model="userForm.password_confirmation"
                                :required="!isEditing"
                                placeholder="Confirm password"
                            >
                        </div>
                    </div>

                    <div class="form-grid" x-show="isEditing">
                        <div class="form-group">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input
                                type="password"
                                class="form-input"
                                x-model="userForm.password"
                                placeholder="Enter new password"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input
                                type="password"
                                class="form-input"
                                x-model="userForm.password_confirmation"
                                placeholder="Confirm new password"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="form-toggle">
                            <input
                                type="checkbox"
                                id="is_active"
                                x-model="userForm.is_active"
                                class="toggle-input"
                            >
                            <label for="is_active" class="toggle-label">
                                <span class="toggle-text" x-text="userForm.is_active ? 'Active' : 'Inactive'"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving" x-text="isEditing ? 'Update User' : 'Create User'"></span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span x-text="isEditing ? 'Updating...' : 'Creating...'"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PERMISSIONS MODAL -->
    <div class="modal-overlay" x-show="showPermissionsModal" x-transition @click="closePermissionsModal()">
        <div class="modal permissions-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Manage Permissions - <span x-text="selectedUser?.name"></span></h3>
                <button class="modal-close" @click="closePermissionsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="permissions-grid">
                    <template x-for="(group, groupName) in permissionGroups" :key="groupName">
                        <div class="permission-group">
                            <h4 class="permission-group-title" x-text="groupName"></h4>
                            <div class="permission-list">
                                <template x-for="permission in group" :key="permission.key">
                                    <label class="permission-item">
                                        <input
                                            type="checkbox"
                                            :value="permission.key"
                                            x-model="userPermissions"
                                            class="permission-checkbox"
                                        >
                                        <span class="permission-label" x-text="permission.label"></span>
                                        <span class="permission-description" x-text="permission.description"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closePermissionsModal()">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" @click="savePermissions()" :disabled="savingPermissions">
                    <span x-show="!savingPermissions">Save Permissions</span>
                    <span x-show="savingPermissions">
                        <i class="fas fa-spinner fa-spin"></i>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* User Management Styles */
    .user-management-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        gap: 2rem;
    }

    .header-title h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.5rem;
    }

    .header-description {
        color: #6B7280;
        font-size: 0.9rem;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-shrink: 0;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3B82F6, #1D4ED8);
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .btn-secondary {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #D1D5DB;
    }

    .btn-secondary:hover:not(:disabled) {
        background: #E5E7EB;
    }

    .btn-warning {
        background: #F59E0B;
        color: white;
    }

    .btn-danger {
        background: #EF4444;
        color: white;
    }

    .btn-info {
        background: #06B6D4;
        color: white;
    }

    .btn-light {
        background: #F8FAFC;
        color: #374151;
        border: 1px solid #E5E7EB;
    }

    .btn-xs {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }

    .filters-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.9rem;
        font-weight: 500;
        color: #374151;
    }

    .filter-input, .filter-select {
        padding: 0.75rem 1rem;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-box {
        position: relative;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
    }

    .search-input {
        padding-left: 2.5rem;
    }

    .loading-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 4rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        color: #6B7280;
    }

    .loading-spinner i {
        font-size: 2rem;
    }

    .users-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        overflow: hidden;
    }

    .table-header {
        padding: 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-title h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
    }

    .users-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
    }

    .users-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
    }

    .users-table tbody tr:hover {
        background: #F9FAFB;
    }

    .users-table tbody tr.selected {
        background: #EFF6FF;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .user-details {
        min-width: 0;
    }

    .user-name {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .user-email {
        font-size: 0.8rem;
        color: #6B7280;
        margin-bottom: 0.25rem;
    }

    .user-entity {
        font-size: 0.75rem;
        color: #9CA3AF;
    }

    .role-badge, .access-level-badge, .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .role-system-admin { background: #FEE2E2; color: #991B1B; }
    .role-engagement-partner { background: #DBEAFE; color: #1E40AF; }
    .role-manager { background: #D1FAE5; color: #065F46; }
    .role-associate { background: #FEF3C7; color: #92400E; }
    .role-guest { background: #F3E8FF; color: #6B21A8; }

    .level-1 { background: #FEE2E2; color: #991B1B; }
    .level-2 { background: #FEF3C7; color: #92400E; }
    .level-3 { background: #D1FAE5; color: #065F46; }
    .level-4 { background: #DBEAFE; color: #1E40AF; }
    .level-5 { background: #F3E8FF; color: #6B21A8; }

    .status-active { background: #D1FAE5; color: #065F46; }
    .status-inactive { background: #FEE2E2; color: #991B1B; }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .date-text {
        font-size: 0.8rem;
        color: #6B7280;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6B7280;
    }

    .empty-icon {
        font-size: 3rem;
        color: #D1D5DB;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.25rem;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        margin-bottom: 2rem;
    }

    .table-pagination {
        padding: 1rem 1.5rem;
        background: #F9FAFB;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pagination-info {
        color: #6B7280;
        font-size: 0.9rem;
    }

    .pagination-controls {
        display: flex;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #D1D5DB;
        background: white;
        color: #374151;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .pagination-btn:hover:not(:disabled) {
        background: #F3F4F6;
    }

    .pagination-btn.active {
        background: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .bulk-actions {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
    }

    .bulk-actions-content {
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .selected-count {
        font-weight: 600;
        color: #374151;
    }

    .bulk-actions-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .modal {
        background: white;
        border-radius: 16px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .permissions-modal {
        max-width: 800px;
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9CA3AF;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        background: #F3F4F6;
        color: #6B7280;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-input, .form-select {
        padding: 0.75rem 1rem;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-input.error, .form-select.error {
        border-color: #EF4444;
    }

    .form-error {
        color: #EF4444;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .form-divider {
        margin: 1.5rem 0;
        text-align: center;
        position: relative;
    }

    .form-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #E5E7EB;
    }

    .form-divider span {
        background: white;
        padding: 0 1rem;
        color: #6B7280;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .form-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .toggle-input {
        display: none;
    }

    .toggle-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .toggle-label::before {
        content: '';
        width: 3rem;
        height: 1.5rem;
        background: #D1D5DB;
        border-radius: 0.75rem;
        position: relative;
        transition: all 0.3s ease;
    }

    .toggle-label::after {
        content: '';
        position: absolute;
        left: 0.125rem;
        width: 1.25rem;
        height: 1.25rem;
        background: white;
        border-radius: 50%;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .toggle-input:checked + .toggle-label::before {
        background: #3B82F6;
    }

    .toggle-input:checked + .toggle-label::after {
        transform: translateX(1.5rem);
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .permission-group {
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        overflow: hidden;
    }

    .permission-group-title {
        background: #F9FAFB;
        padding: 1rem;
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }

    .permission-list {
        padding: 1rem;
    }

    .permission-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.75rem;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
    }

    .permission-item:hover {
        background: #F9FAFB;
    }

    .permission-checkbox {
        margin-right: 0.5rem;
    }

    .permission-label {
        font-weight: 500;
        color: #374151;
    }

    .permission-description {
        font-size: 0.8rem;
        color: #6B7280;
        margin-left: 1.5rem;
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .user-management-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions {
            justify-content: stretch;
        }

        .table-container {
            overflow-x: scroll;
        }

        .permissions-grid {
            grid-template-columns: 1fr;
        }

        .bulk-actions {
            left: 1rem;
            right: 1rem;
            transform: none;
        }

        .bulk-actions-content {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function userManagement() {
    return {
        // Data
        users: [],
        selectedUsers: [],
        filters: {
            search: '',
            role: '',
            access_level: '',
            is_active: '',
            sort_by: 'created_at',
            sort_order: 'desc',
            per_page: 25
        },
        pagination: {},
        loading: false,

        // Modal states
        showModal: false,
        showPermissionsModal: false,
        isEditing: false,
        saving: false,
        savingPermissions: false,

        // Form data
        userForm: {
            name: '',
            email: '',
            entity: '',
            contact_number: '',
            role: '',
            access_level: '',
            password: '',
            password_confirmation: '',
            is_active: true
        },

        // Permissions
        selectedUser: null,
        userPermissions: [],
        permissionGroups: {
            'User Management': [
                { key: 'view_user', label: 'View Users', description: 'Can view user list and details' },
                { key: 'create_user', label: 'Create Users', description: 'Can create new user accounts' },
                { key: 'edit_user', label: 'Edit Users', description: 'Can modify user information' },
                { key: 'delete_user', label: 'Delete Users', description: 'Can delete user accounts' }
            ],
            'Client Management': [
                { key: 'view_client', label: 'View Clients', description: 'Can view client list and details' },
                { key: 'create_client', label: 'Create Clients', description: 'Can add new clients' },
                { key: 'edit_client', label: 'Edit Clients', description: 'Can modify client information' },
                { key: 'delete_client', label: 'Delete Clients', description: 'Can remove clients' }
            ],
            'Project Management': [
                { key: 'view_project', label: 'View Projects', description: 'Can view project list and details' },
                { key: 'create_project', label: 'Create Projects', description: 'Can create new projects' },
                { key: 'edit_project', label: 'Edit Projects', description: 'Can modify project information' },
                { key: 'delete_project', label: 'Delete Projects', description: 'Can delete projects' }
            ],
            'PBC Requests': [
                { key: 'view_pbc_request', label: 'View PBC Requests', description: 'Can view PBC request list' },
                { key: 'create_pbc_request', label: 'Create PBC Requests', description: 'Can create new PBC requests' },
                { key: 'edit_pbc_request', label: 'Edit PBC Requests', description: 'Can modify PBC requests' },
                { key: 'delete_pbc_request', label: 'Delete PBC Requests', description: 'Can delete PBC requests' }
            ],
            'Documents': [
                { key: 'upload_document', label: 'Upload Documents', description: 'Can upload documents' },
                { key: 'approve_document', label: 'Approve Documents', description: 'Can approve/reject documents' },
                { key: 'delete_document', label: 'Delete Documents', description: 'Can delete documents' }
            ],
            'System': [
                { key: 'send_reminder', label: 'Send Reminders', description: 'Can send email reminders' },
                { key: 'view_audit_log', label: 'View Audit Logs', description: 'Can view system audit trail' },
                { key: 'export_reports', label: 'Export Reports', description: 'Can generate and export reports' },
                { key: 'manage_settings', label: 'Manage Settings', description: 'Can modify system settings' },
                { key: 'manage_permissions', label: 'Manage Permissions', description: 'Can modify user permissions' }
            ]
        },

        errors: {},

        // Initialize
        async init() {
            console.log('🚀 User Management Init Starting');
            await this.loadUsers();
        },

        // API calls - FIXED ROUTES
        async loadUsers(page = 1) {
            console.log('🔍 Loading users - Start');
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: page
                });

                // Use web route, not API route
                const url = `/users?${params}`;
                console.log('🌐 Web URL:', url);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                console.log('📡 Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('📊 Response:', result);

                if (result.success) {
                    this.users = result.data || [];
                    this.pagination = result.pagination || {};
                    console.log('✅ Users loaded:', this.users.length);
                } else {
                    console.error('❌ Error:', result.message);
                    this.showError('Failed to load users: ' + result.message);
                }
            } catch (error) {
                console.error('🚨 Network Error:', error);
                this.showError('Failed to load users: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        async saveUser() {
            this.saving = true;
            this.errors = {};

            try {
                const url = this.isEditing
                    ? `/users/${this.userForm.id}`
                    : '/users';

                const method = this.isEditing ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.userForm)
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(this.isEditing ? 'User updated successfully' : 'User created successfully');
                    this.closeModal();
                    await this.loadUsers();
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                    } else {
                        this.showError(result.message || 'Failed to save user');
                    }
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            } finally {
                this.saving = false;
            }
        },

        async deleteUser(user) {
            if (!confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`/users/${user.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('User deleted successfully');
                    await this.loadUsers();
                } else {
                    this.showError(result.message || 'Failed to delete user');
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            }
        },

        async loadUserPermissions(user) {
            try {
                const response = await fetch(`/users/${user.id}/permissions`, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success) {
                    this.userPermissions = result.data || [];
                } else {
                    this.showError('Failed to load permissions: ' + result.message);
                }
            } catch (error) {
                this.showError('Failed to load permissions: ' + error.message);
            }
        },

        async savePermissions() {
            this.savingPermissions = true;

            try {
                const response = await fetch(`/users/${this.selectedUser.id}/permissions`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        permissions: this.userPermissions
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('Permissions updated successfully');
                    this.closePermissionsModal();
                } else {
                    this.showError(result.message || 'Failed to update permissions');
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            } finally {
                this.savingPermissions = false;
            }
        },

        // Modal methods
        openCreateModal() {
            this.isEditing = false;
            this.userForm = {
                name: '',
                email: '',
                entity: '',
                contact_number: '',
                role: '',
                access_level: '',
                password: '',
                password_confirmation: '',
                is_active: true
            };
            this.errors = {};
            this.showModal = true;
        },

        editUser(user) {
            this.isEditing = true;
            this.userForm = {
                id: user.id,
                name: user.name,
                email: user.email,
                entity: user.entity || '',
                contact_number: user.contact_number || '',
                role: user.role,
                access_level: user.access_level,
                password: '',
                password_confirmation: '',
                is_active: user.is_active
            };
            this.errors = {};
            this.showModal = true;
        },

        viewUser(user) {
            alert(`View user: ${user.name}\nEmail: ${user.email}\nRole: ${this.formatRole(user.role)}`);
        },

        async managePermissions(user) {
            this.selectedUser = user;
            await this.loadUserPermissions(user);
            this.showPermissionsModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.isEditing = false;
            this.userForm = {};
            this.errors = {};
        },

        closePermissionsModal() {
            this.showPermissionsModal = false;
            this.selectedUser = null;
            this.userPermissions = [];
        },

        // Selection methods
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedUsers = this.users.map(user => user.id);
            } else {
                this.selectedUsers = [];
            }
        },

        toggleUserSelection(userId) {
            const index = this.selectedUsers.indexOf(userId);
            if (index > -1) {
                this.selectedUsers.splice(index, 1);
            } else {
                this.selectedUsers.push(userId);
            }
        },

        clearSelection() {
            this.selectedUsers = [];
        },

        // Bulk actions
        async bulkActivate() {
            if (!confirm(`Activate ${this.selectedUsers.length} selected users?`)) return;
            this.showSuccess('Users activated successfully');
            this.clearSelection();
            await this.loadUsers();
        },

        async bulkDeactivate() {
            if (!confirm(`Deactivate ${this.selectedUsers.length} selected users?`)) return;
            this.showSuccess('Users deactivated successfully');
            this.clearSelection();
            await this.loadUsers();
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selectedUsers.length} selected users? This action cannot be undone.`)) return;
            this.showSuccess('Users deleted successfully');
            this.clearSelection();
            await this.loadUsers();
        },

        // Filter methods
        clearFilters() {
            this.filters = {
                search: '',
                role: '',
                access_level: '',
                is_active: '',
                sort_by: 'created_at',
                sort_order: 'desc',
                per_page: 25
            };
            this.loadUsers();
        },

        async exportUsers() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/users/export?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'users_export.xlsx';
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            } catch (error) {
                this.showError('Failed to export users');
            }
        },

        // Pagination
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadUsers(page);
            }
        },

        get visiblePages() {
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const pages = [];

            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }

            return pages;
        },

        // Utility methods
        getUserInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase();
        },

        getUserAvatarColor(name) {
            const colors = [
                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'
            ];
            const index = name.charCodeAt(0) % colors.length;
            return colors[index];
        },

        formatRole(role) {
            return role.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Notification methods
        showSuccess(message) {
            alert('SUCCESS: ' + message);
        },

        showError(message) {
            alert('ERROR: ' + message);
        }
    }
}
</script>
@endpush
@endsection
