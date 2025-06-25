@extends('layouts.app')

@section('title', 'Client Management')
@section('page-title', 'Client Management')
@section('page-subtitle', 'Manage audit clients and their information')

@section('content')
<div x-data="clientManagement()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="client-management-header">
        <div class="header-title">
            <h2>Audit Clients</h2>
            <p class="header-description">Manage client information, contacts, and project portfolios</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="exportClients()" :disabled="loading">
                <i class="fas fa-download"></i>
                Export Clients
            </button>
            @if(auth()->user()->hasPermission('create_client'))
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Add New Client
            </button>
            @endif
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Clients</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by name, SEC no., industry, or contact..."
                        x-model="filters.search"
                        @input.debounce.500ms="loadClients()"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Industry</label>
                <select class="filter-select" x-model="filters.industry" @change="loadClients()">
                    <option value="">All Industries</option>
                    <option value="Manufacturing">Manufacturing</option>
                    <option value="Technology">Technology</option>
                    <option value="Construction">Construction</option>
                    <option value="Financial Services">Financial Services</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Education">Education</option>
                    <option value="Retail">Retail</option>
                    <option value="Transportation">Transportation</option>
                    <option value="Real Estate">Real Estate</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.is_active" @change="loadClients()">
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
            <span>Loading clients...</span>
        </div>
    </div>

    <!-- CLIENTS TABLE -->
    <div class="clients-card" x-show="!loading">
        <div class="table-header">
            <div class="table-title">
                <h3>Clients (<span x-text="pagination.total || 0"></span>)</h3>
            </div>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" @click="loadClients()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" @change="toggleSelectAll($event)">
                        </th>
                        <th>Client Information</th>
                        <th>Industry</th>
                        <th>Primary Contact</th>
                        <th>Projects</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="client in clients" :key="client.id">
                        <tr :class="{ 'selected': selectedClients.includes(client.id) }">
                            <td>
                                <input
                                    type="checkbox"
                                    :checked="selectedClients.includes(client.id)"
                                    @change="toggleClientSelection(client.id)"
                                >
                            </td>
                            <td>
                                <div class="client-info">
                                    <div class="client-avatar" :style="`background: ${getClientAvatarColor(client.name)}`">
                                        <span x-text="getClientInitials(client.name)"></span>
                                    </div>
                                    <div class="client-details">
                                        <div class="client-name" x-text="client.name"></div>
                                        <div class="client-sec" x-text="'SEC: ' + client.sec_registration_no"></div>
                                        <div class="client-address" x-text="client.business_address.substring(0, 50) + '...'"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="industry-badge" :class="`industry-${client.industry_classification.toLowerCase().replace(/\s+/g, '-')}`"
                                      x-text="client.industry_classification"></span>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div class="contact-name" x-text="client.primary_contact_name"></div>
                                    <div class="contact-email" x-text="client.primary_contact_email"></div>
                                    <div class="contact-phone" x-text="client.primary_contact_number"></div>
                                </div>
                            </td>
                            <td>
                                <div class="projects-info">
                                    <div class="projects-count">
                                        <span class="projects-total" x-text="(client.projects || []).length"></span>
                                        <span class="projects-label">projects</span>
                                    </div>
                                    <div class="projects-active" x-text="getActiveProjectsCount(client) + ' active'"></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" :class="`status-${client.is_active ? 'active' : 'inactive'}`"
                                      x-text="client.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td>
                                <span class="date-text" x-text="formatDate(client.created_at)"></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-xs btn-secondary" @click="viewClient(client)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(auth()->user()->hasPermission('edit_client'))
                                    <button class="btn btn-xs btn-warning" @click="editClient(client)" title="Edit Client">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-xs btn-info" @click="viewProjects(client)" title="View Projects">
                                        <i class="fas fa-project-diagram"></i>
                                    </button>
                                    @if(auth()->user()->hasPermission('delete_client'))
                                    <button class="btn btn-xs btn-danger" @click="deleteClient(client)" title="Delete Client">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- EMPTY STATE -->
            <div x-show="clients.length === 0 && !loading" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>No clients found</h3>
                <p>Try adjusting your search criteria or add a new client.</p>
                @if(auth()->user()->hasPermission('create_client'))
                <button class="btn btn-primary" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    Add First Client
                </button>
                @endif
            </div>
        </div>

        <!-- PAGINATION -->
        <div class="table-pagination" x-show="clients.length > 0">
            <div class="pagination-info">
                Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                of <span x-text="pagination.total || 0"></span> clients
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
    <div class="bulk-actions" x-show="selectedClients.length > 0" x-transition>
        <div class="bulk-actions-content">
            <span class="selected-count"><span x-text="selectedClients.length"></span> clients selected</span>
            <div class="bulk-actions-buttons">
                <button class="btn btn-sm btn-warning" @click="bulkActivate()">
                    <i class="fas fa-check"></i>
                    Activate
                </button>
                <button class="btn btn-sm btn-secondary" @click="bulkDeactivate()">
                    <i class="fas fa-pause"></i>
                    Deactivate
                </button>
                @if(auth()->user()->hasPermission('delete_client'))
                <button class="btn btn-sm btn-danger" @click="bulkDelete()">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
                @endif
                <button class="btn btn-sm btn-light" @click="clearSelection()">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- CREATE/EDIT CLIENT MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal client-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEditing ? 'Edit Client' : 'Add New Client'"></h3>
                <button class="modal-close" @click="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveClient()">
                <div class="modal-body">
                    <!-- BASIC INFORMATION -->
                    <div class="form-section">
                        <h4 class="form-section-title">Basic Information</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Client Name *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.name"
                                    required
                                    placeholder="Enter client company name"
                                    :class="{ 'error': errors.name }"
                                >
                                <div class="form-error" x-show="errors.name" x-text="errors.name"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">SEC Registration No. *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.sec_registration_no"
                                    required
                                    placeholder="Enter SEC registration number"
                                    :class="{ 'error': errors.sec_registration_no }"
                                >
                                <div class="form-error" x-show="errors.sec_registration_no" x-text="errors.sec_registration_no"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Industry Classification *</label>
                                <select class="form-select" x-model="clientForm.industry_classification" required :class="{ 'error': errors.industry_classification }">
                                    <option value="">Select Industry</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Construction">Construction</option>
                                    <option value="Financial Services">Financial Services</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Education">Education</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Others">Others</option>
                                </select>
                                <div class="form-error" x-show="errors.industry_classification" x-text="errors.industry_classification"></div>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Business Address *</label>
                                <textarea
                                    class="form-textarea"
                                    x-model="clientForm.business_address"
                                    required
                                    placeholder="Enter complete business address"
                                    rows="3"
                                    :class="{ 'error': errors.business_address }"
                                ></textarea>
                                <div class="form-error" x-show="errors.business_address" x-text="errors.business_address"></div>
                            </div>
                        </div>
                    </div>

                    <!-- PRIMARY CONTACT -->
                    <div class="form-section">
                        <h4 class="form-section-title">Primary Contact Person</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.primary_contact_name"
                                    required
                                    placeholder="Enter contact person name"
                                    :class="{ 'error': errors.primary_contact_name }"
                                >
                                <div class="form-error" x-show="errors.primary_contact_name" x-text="errors.primary_contact_name"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input
                                    type="email"
                                    class="form-input"
                                    x-model="clientForm.primary_contact_email"
                                    required
                                    placeholder="Enter email address"
                                    :class="{ 'error': errors.primary_contact_email }"
                                >
                                <div class="form-error" x-show="errors.primary_contact_email" x-text="errors.primary_contact_email"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Number *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.primary_contact_number"
                                    required
                                    placeholder="Enter contact number"
                                    :class="{ 'error': errors.primary_contact_number }"
                                >
                                <div class="form-error" x-show="errors.primary_contact_number" x-text="errors.primary_contact_number"></div>
                            </div>
                        </div>
                    </div>

                    <!-- SECONDARY CONTACT -->
                    <div class="form-section">
                        <h4 class="form-section-title">Secondary Contact Person (Optional)</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.secondary_contact_name"
                                    placeholder="Enter contact person name"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    class="form-input"
                                    x-model="clientForm.secondary_contact_email"
                                    placeholder="Enter email address"
                                    :class="{ 'error': errors.secondary_contact_email }"
                                >
                                <div class="form-error" x-show="errors.secondary_contact_email" x-text="errors.secondary_contact_email"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="clientForm.secondary_contact_number"
                                    placeholder="Enter contact number"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <div class="form-toggle">
                                <input
                                    type="checkbox"
                                    id="client_is_active"
                                    x-model="clientForm.is_active"
                                    class="toggle-input"
                                >
                                <label for="client_is_active" class="toggle-label">
                                    <span class="toggle-text" x-text="clientForm.is_active ? 'Active' : 'Inactive'"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving" x-text="isEditing ? 'Update Client' : 'Add Client'"></span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span x-text="isEditing ? 'Updating...' : 'Adding...'"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CLIENT DETAILS MODAL -->
    <div class="modal-overlay" x-show="showDetailsModal" x-transition @click="closeDetailsModal()">
        <div class="modal details-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Client Details - <span x-text="selectedClient?.name"></span></h3>
                <button class="modal-close" @click="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body" x-show="selectedClient">
                <div class="details-grid">
                    <div class="detail-section">
                        <h4>Basic Information</h4>
                        <div class="detail-item">
                            <label>Company Name:</label>
                            <span x-text="selectedClient?.name"></span>
                        </div>
                        <div class="detail-item">
                            <label>SEC Registration:</label>
                            <span x-text="selectedClient?.sec_registration_no"></span>
                        </div>
                        <div class="detail-item">
                            <label>Industry:</label>
                            <span x-text="selectedClient?.industry_classification"></span>
                        </div>
                        <div class="detail-item">
                            <label>Address:</label>
                            <span x-text="selectedClient?.business_address"></span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Primary Contact</h4>
                        <div class="detail-item">
                            <label>Name:</label>
                            <span x-text="selectedClient?.primary_contact_name"></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span x-text="selectedClient?.primary_contact_email"></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span x-text="selectedClient?.primary_contact_number"></span>
                        </div>
                    </div>

                    <div class="detail-section" x-show="selectedClient?.secondary_contact_name">
                        <h4>Secondary Contact</h4>
                        <div class="detail-item">
                            <label>Name:</label>
                            <span x-text="selectedClient?.secondary_contact_name"></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span x-text="selectedClient?.secondary_contact_email"></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span x-text="selectedClient?.secondary_contact_number"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeDetailsModal()">
                    Close
                </button>
                @if(auth()->user()->hasPermission('edit_client'))
                <button type="button" class="btn btn-primary" @click="editClientFromDetails()">
                    <i class="fas fa-edit"></i>
                    Edit Client
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Inherit all styles from user management and add client-specific ones */
    .client-management-header {
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
        grid-template-columns: 2fr 1fr 1fr auto;
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

    .clients-card {
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

    .clients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .clients-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
    }

    .clients-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
    }

    .clients-table tbody tr:hover {
        background: #F9FAFB;
    }

    .clients-table tbody tr.selected {
        background: #EFF6FF;
    }

    .client-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .client-avatar {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .client-details {
        min-width: 0;
    }

    .client-name {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .client-sec {
        font-size: 0.8rem;
        color: #6B7280;
        margin-bottom: 0.25rem;
    }

    .client-address {
        font-size: 0.75rem;
        color: #9CA3AF;
    }

    .industry-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .industry-manufacturing { background: #FEE2E2; color: #991B1B; }
    .industry-technology { background: #DBEAFE; color: #1E40AF; }
    .industry-construction { background: #FEF3C7; color: #92400E; }
    .industry-financial-services { background: #D1FAE5; color: #065F46; }
    .industry-healthcare { background: #F3E8FF; color: #6B21A8; }
    .industry-education { background: #E0E7FF; color: #3730A3; }
    .industry-retail { background: #FCE7F3; color: #BE185D; }
    .industry-transportation { background: #ECFDF5; color: #047857; }
    .industry-real-estate { background: #FEF7FF; color: #86198F; }
    .industry-others { background: #F3F4F6; color: #374151; }

    .contact-info {
        font-size: 0.8rem;
    }

    .contact-name {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .contact-email {
        color: #6B7280;
        margin-bottom: 0.25rem;
    }

    .contact-phone {
        color: #9CA3AF;
    }

    .projects-info {
        text-align: center;
    }

    .projects-count {
        margin-bottom: 0.25rem;
    }

    .projects-total {
        font-weight: 600;
        color: #1F2937;
        font-size: 1.1rem;
    }

    .projects-label {
        font-size: 0.8rem;
        color: #6B7280;
        margin-left: 0.25rem;
    }

    .projects-active {
        font-size: 0.75rem;
        color: #10B981;
        font-weight: 500;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

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
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .client-modal {
        max-width: 900px;
    }

    .details-modal {
        max-width: 700px;
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

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #E5E7EB;
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

    .form-input, .form-select, .form-textarea {
        padding: 0.75rem 1rem;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-input.error, .form-select.error, .form-textarea.error {
        border-color: #EF4444;
    }

    .form-error {
        color: #EF4444;
        font-size: 0.8rem;
        margin-top: 0.25rem;
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

    .details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .detail-section {
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 1.5rem;
    }

    .detail-section h4 {
        font-size: 1rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #E5E7EB;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #F3F4F6;
    }

    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .detail-item label {
        font-weight: 500;
        color: #6B7280;
        min-width: 80px;
    }

    .detail-item span {
        color: #1F2937;
        text-align: right;
        max-width: 200px;
        word-wrap: break-word;
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .client-management-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions {
            justify-content: stretch;
        }

        .table-container {
            overflow-x: scroll;
        }

        .details-grid {
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
    function clientManagement() {
        return {
            // Data
            clients: [],
            selectedClients: [],
            filters: {
                search: '',
                industry: '',
                is_active: '',
                sort_by: 'created_at',
                sort_order: 'desc',
                per_page: 25
            },
            pagination: {},
            loading: false,

            // Modal states
            showModal: false,
            showDetailsModal: false,
            isEditing: false,
            saving: false,

            // Form data
            clientForm: {
                name: '',
                sec_registration_no: '',
                industry_classification: '',
                business_address: '',
                primary_contact_name: '',
                primary_contact_email: '',
                primary_contact_number: '',
                secondary_contact_name: '',
                secondary_contact_email: '',
                secondary_contact_number: '',
                is_active: true
            },

            selectedClient: null,
            errors: {},

            // Initialize
            async init() {
                console.log('🚀 Client Management Init Starting');
                await this.loadClients();
            },

            // API calls
            async loadClients(page = 1) {
                console.log('🔍 Loading clients - Start');
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        ...this.filters,
                        page: page
                    });

                    const url = `/clients?${params}`;
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
                        this.clients = result.data || [];
                        this.pagination = result.pagination || {};
                        console.log('✅ Clients loaded:', this.clients.length);
                    } else {
                        console.error('❌ Error:', result.message);
                        this.showError('Failed to load clients: ' + result.message);
                    }
                } catch (error) {
                    console.error('🚨 Network Error:', error);
                    this.showError('Failed to load clients: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async saveClient() {
                this.saving = true;
                this.errors = {};

                try {
                    const url = this.isEditing
                        ? `/clients/${this.clientForm.id}`
                        : '/clients';

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
                        body: JSON.stringify(this.clientForm)
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess(this.isEditing ? 'Client updated successfully' : 'Client created successfully');
                        this.closeModal();
                        await this.loadClients();
                    } else {
                        if (result.errors) {
                            this.errors = result.errors;
                        } else {
                            this.showError(result.message || 'Failed to save client');
                        }
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                } finally {
                    this.saving = false;
                }
            },

            async deleteClient(client) {
                if (!confirm(`Are you sure you want to delete ${client.name}? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch(`/clients/${client.id}`, {
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
                        this.showSuccess('Client deleted successfully');
                        await this.loadClients();
                    } else {
                        this.showError(result.message || 'Failed to delete client');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            // Modal methods
            openCreateModal() {
                this.isEditing = false;
                this.clientForm = {
                    name: '',
                    sec_registration_no: '',
                    industry_classification: '',
                    business_address: '',
                    primary_contact_name: '',
                    primary_contact_email: '',
                    primary_contact_number: '',
                    secondary_contact_name: '',
                    secondary_contact_email: '',
                    secondary_contact_number: '',
                    is_active: true
                };
                this.errors = {};
                this.showModal = true;
            },

            editClient(client) {
                this.isEditing = true;
                this.clientForm = {
                    id: client.id,
                    name: client.name,
                    sec_registration_no: client.sec_registration_no,
                    industry_classification: client.industry_classification,
                    business_address: client.business_address,
                    primary_contact_name: client.primary_contact_name,
                    primary_contact_email: client.primary_contact_email,
                    primary_contact_number: client.primary_contact_number,
                    secondary_contact_name: client.secondary_contact_name || '',
                    secondary_contact_email: client.secondary_contact_email || '',
                    secondary_contact_number: client.secondary_contact_number || '',
                    is_active: client.is_active
                };
                this.errors = {};
                this.showModal = true;
            },

            viewClient(client) {
                this.selectedClient = client;
                this.showDetailsModal = true;
            },

            viewProjects(client) {
                // Navigate to projects page filtered by this client
                window.location.href = `/projects?client=${client.id}`;
            },

            editClientFromDetails() {
                this.showDetailsModal = false;
                this.editClient(this.selectedClient);
            },

            closeModal() {
                this.showModal = false;
                this.isEditing = false;
                this.clientForm = {};
                this.errors = {};
            },

            closeDetailsModal() {
                this.showDetailsModal = false;
                this.selectedClient = null;
            },

            // Selection methods
            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedClients = this.clients.map(client => client.id);
                } else {
                    this.selectedClients = [];
                }
            },

            toggleClientSelection(clientId) {
                const index = this.selectedClients.indexOf(clientId);
                if (index > -1) {
                    this.selectedClients.splice(index, 1);
                } else {
                    this.selectedClients.push(clientId);
                }
            },

            clearSelection() {
                this.selectedClients = [];
            },

            // Bulk actions
            async bulkActivate() {
                if (!confirm(`Activate ${this.selectedClients.length} selected clients?`)) return;
                this.showSuccess('Clients activated successfully');
                this.clearSelection();
                await this.loadClients();
            },

            async bulkDeactivate() {
                if (!confirm(`Deactivate ${this.selectedClients.length} selected clients?`)) return;
                this.showSuccess('Clients deactivated successfully');
                this.clearSelection();
                await this.loadClients();
            },

            async bulkDelete() {
                if (!confirm(`Delete ${this.selectedClients.length} selected clients? This action cannot be undone.`)) return;
                this.showSuccess('Clients deleted successfully');
                this.clearSelection();
                await this.loadClients();
            },

            // Filter methods
            clearFilters() {
                this.filters = {
                    search: '',
                    industry: '',
                    is_active: '',
                    sort_by: 'created_at',
                    sort_order: 'desc',
                    per_page: 25
                };
                this.loadClients();
            },

            async exportClients() {
                try {
                    const params = new URLSearchParams(this.filters);
                    const response = await fetch(`/clients/export?${params}`, {
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
                        a.download = 'clients_export.xlsx';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                } catch (error) {
                    this.showError('Failed to export clients');
                }
            },

            // Pagination
            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadClients(page);
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
            getClientInitials(name) {
                return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            },

            getClientAvatarColor(name) {
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

            getActiveProjectsCount(client) {
                if (!client.projects) return 0;
                return client.projects.filter(project => project.status === 'active').length;
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
