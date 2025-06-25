@extends('layouts.app')

@section('title', 'Project Management')
@section('page-title', 'Project Management')
@section('page-subtitle', 'Manage audit engagements, teams, and project progress')

@section('content')
<div x-data="projectManagement()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="project-management-header">
        <div class="header-title">
            <h2>Audit Projects</h2>
            <p class="header-description">Manage project engagements, team assignments, and track progress</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="exportProjects()" :disabled="loading">
                <i class="fas fa-download"></i>
                Export Projects
            </button>
            @if(auth()->user()->hasPermission('create_project'))
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Create Project
            </button>
            @endif
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Projects</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by client, contact, or notes..."
                        x-model="filters.search"
                        @input.debounce.500ms="loadProjects()"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Client</label>
                <select class="filter-select" x-model="filters.client_id" @change="loadProjects()">
                    <option value="">All Clients</option>
                    <template x-for="client in availableClients" :key="client.id">
                        <option :value="client.id" x-text="client.name"></option>
                    </template>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Engagement Type</label>
                <select class="filter-select" x-model="filters.engagement_type" @change="loadProjects()">
                    <option value="">All Types</option>
                    <option value="audit">Audit</option>
                    <option value="accounting">Accounting</option>
                    <option value="tax">Tax</option>
                    <option value="special_engagement">Special Engagement</option>
                    <option value="others">Others</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.status" @change="loadProjects()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="on_hold">On Hold</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Team Member</label>
                <select class="filter-select" x-model="filters.engagement_partner_id" @change="loadProjects()">
                    <option value="">All Team Members</option>
                    <template x-for="user in availableUsers" :key="user.id">
                        <option :value="user.id" x-text="user.name + ' (' + formatRole(user.role) + ')'"></option>
                    </template>
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
            <span>Loading projects...</span>
        </div>
    </div>

    <!-- PROJECTS TABLE -->
    <div class="projects-card" x-show="!loading">
        <div class="table-header">
            <div class="table-title">
                <h3>Projects (<span x-text="pagination.total || 0"></span>)</h3>
            </div>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" @click="loadProjects()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" @change="toggleSelectAll($event)">
                        </th>
                        <th>Project Details</th>
                        <th>Engagement Type</th>
                        <th>Team</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="project in projects" :key="project.id">
                        <tr :class="{ 'selected': selectedProjects.includes(project.id) }">
                            <td>
                                <input
                                    type="checkbox"
                                    :checked="selectedProjects.includes(project.id)"
                                    @change="toggleProjectSelection(project.id)"
                                >
                            </td>
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar" :style="`background: ${getProjectAvatarColor(project.client?.name || 'Unknown')}`">
                                        <span x-text="getProjectInitials(project.client?.name || 'UK')"></span>
                                    </div>
                                    <div class="project-details">
                                        <div class="project-client" x-text="project.client?.name || 'Unknown Client'"></div>
                                        <div class="project-contact" x-text="project.contact_person"></div>
                                        <div class="project-contact-email" x-text="project.contact_email"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="engagement-badge" :class="`engagement-${project.engagement_type.replace('_', '-')}`"
                                      x-text="formatEngagementType(project.engagement_type)"></span>
                            </td>
                            <td>
                                <div class="team-info">
                                    <div class="team-member" x-show="project.engagement_partner">
                                        <i class="fas fa-user-tie"></i>
                                        <span x-text="project.engagement_partner?.name || 'No Partner'"></span>
                                    </div>
                                    <div class="team-member" x-show="project.manager">
                                        <i class="fas fa-user-cog"></i>
                                        <span x-text="project.manager?.name || 'No Manager'"></span>
                                    </div>
                                    <div class="team-associates" x-show="project.associate_1 || project.associate_2">
                                        <span class="associates-count" x-text="getAssociatesCount(project) + ' associate(s)'"></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-info">
                                    <div class="progress-bar">
                                        <div class="progress-fill" :style="`width: ${project.progress_percentage || 0}%`"></div>
                                    </div>
                                    <div class="progress-text" x-text="(project.progress_percentage || 0) + '%'"></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" :class="`status-${project.status.replace('_', '-')}`"
                                      x-text="formatStatus(project.status)"></span>
                            </td>
                            <td>
                                <span class="date-text" x-text="formatDate(project.engagement_period)"></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-xs btn-secondary" @click="viewProject(project)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(auth()->user()->hasPermission('edit_project'))
                                    <button class="btn btn-xs btn-warning" @click="editProject(project)" title="Edit Project">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-xs btn-info" @click="viewPbcRequests(project)" title="View PBC Requests">
                                        <i class="fas fa-tasks"></i>
                                    </button>
                                    @if(auth()->user()->hasPermission('delete_project'))
                                    <button class="btn btn-xs btn-danger" @click="deleteProject(project)" title="Delete Project">
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
            <div x-show="projects.length === 0 && !loading" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <h3>No projects found</h3>
                <p>Try adjusting your search criteria or create a new project.</p>
                @if(auth()->user()->hasPermission('create_project'))
                <button class="btn btn-primary" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create First Project
                </button>
                @endif
            </div>
        </div>

        <!-- PAGINATION -->
        <div class="table-pagination" x-show="projects.length > 0">
            <div class="pagination-info">
                Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                of <span x-text="pagination.total || 0"></span> projects
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
    <div class="bulk-actions" x-show="selectedProjects.length > 0" x-transition>
        <div class="bulk-actions-content">
            <span class="selected-count"><span x-text="selectedProjects.length"></span> projects selected</span>
            <div class="bulk-actions-buttons">
                <button class="btn btn-sm btn-warning" @click="bulkUpdateStatus('active')">
                    <i class="fas fa-play"></i>
                    Activate
                </button>
                <button class="btn btn-sm btn-secondary" @click="bulkUpdateStatus('on_hold')">
                    <i class="fas fa-pause"></i>
                    On Hold
                </button>
                <button class="btn btn-sm btn-success" @click="bulkUpdateStatus('completed')">
                    <i class="fas fa-check"></i>
                    Complete
                </button>
                @if(auth()->user()->hasPermission('delete_project'))
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

    <!-- CREATE/EDIT PROJECT MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal project-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEditing ? 'Edit Project' : 'Create New Project'"></h3>
                <button class="modal-close" @click="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveProject()">
                <div class="modal-body">
                    <!-- PROJECT INFORMATION -->
                    <div class="form-section">
                        <h4 class="form-section-title">Project Information</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Client *</label>
                                <select class="form-select" x-model="projectForm.client_id" required :class="{ 'error': errors.client_id }">
                                    <option value="">Select Client</option>
                                    <template x-for="client in availableClients" :key="client.id">
                                        <option :value="client.id" x-text="client.name"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.client_id" x-text="errors.client_id"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Engagement Type *</label>
                                <select class="form-select" x-model="projectForm.engagement_type" required :class="{ 'error': errors.engagement_type }">
                                    <option value="">Select Type</option>
                                    <option value="audit">Audit</option>
                                    <option value="accounting">Accounting</option>
                                    <option value="tax">Tax</option>
                                    <option value="special_engagement">Special Engagement</option>
                                    <option value="others">Others</option>
                                </select>
                                <div class="form-error" x-show="errors.engagement_type" x-text="errors.engagement_type"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Engagement Period *</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    x-model="projectForm.engagement_period"
                                    required
                                    :class="{ 'error': errors.engagement_period }"
                                >
                                <div class="form-error" x-show="errors.engagement_period" x-text="errors.engagement_period"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-select" x-model="projectForm.status">
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- CONTACT INFORMATION -->
                    <div class="form-section">
                        <h4 class="form-section-title">Contact Information</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Contact Person *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="projectForm.contact_person"
                                    required
                                    placeholder="Enter contact person name"
                                    :class="{ 'error': errors.contact_person }"
                                >
                                <div class="form-error" x-show="errors.contact_person" x-text="errors.contact_person"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Email *</label>
                                <input
                                    type="email"
                                    class="form-input"
                                    x-model="projectForm.contact_email"
                                    required
                                    placeholder="Enter contact email"
                                    :class="{ 'error': errors.contact_email }"
                                >
                                <div class="form-error" x-show="errors.contact_email" x-text="errors.contact_email"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Number *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="projectForm.contact_number"
                                    required
                                    placeholder="Enter contact number"
                                    :class="{ 'error': errors.contact_number }"
                                >
                                <div class="form-error" x-show="errors.contact_number" x-text="errors.contact_number"></div>
                            </div>
                        </div>
                    </div>

                    <!-- TEAM ASSIGNMENT -->
                    <div class="form-section">
                        <h4 class="form-section-title">Team Assignment</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Engagement Partner</label>
                                <select class="form-select" x-model="projectForm.engagement_partner_id">
                                    <option value="">Select Engagement Partner</option>
                                    <template x-for="user in getEngagementPartners()" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Manager</label>
                                <select class="form-select" x-model="projectForm.manager_id">
                                    <option value="">Select Manager</option>
                                    <template x-for="user in getManagers()" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Associate 1</label>
                                <select class="form-select" x-model="projectForm.associate_1_id">
                                    <option value="">Select Associate 1</option>
                                    <template x-for="user in getAssociates()" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Associate 2</label>
                                <select class="form-select" x-model="projectForm.associate_2_id">
                                    <option value="">Select Associate 2</option>
                                    <template x-for="user in getAssociates()" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- PROGRESS AND NOTES -->
                    <div class="form-section">
                        <h4 class="form-section-title">Progress & Notes</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Progress Percentage</label>
                                <div class="progress-input-group">
                                    <input
                                        type="number"
                                        class="form-input"
                                        x-model="projectForm.progress_percentage"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="0.00"
                                    >
                                    <span class="progress-suffix">%</span>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea
                                    class="form-textarea"
                                    x-model="projectForm.notes"
                                    placeholder="Enter project notes or additional information"
                                    rows="3"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving" x-text="isEditing ? 'Update Project' : 'Create Project'"></span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span x-text="isEditing ? 'Updating...' : 'Creating...'"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PROJECT DETAILS MODAL -->
    <div class="modal-overlay" x-show="showDetailsModal" x-transition @click="closeDetailsModal()">
        <div class="modal details-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Project Details - <span x-text="selectedProject?.client?.name"></span></h3>
                <button class="modal-close" @click="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body" x-show="selectedProject">
                <div class="details-grid">
                    <div class="detail-section">
                        <h4>Project Information</h4>
                        <div class="detail-item">
                            <label>Client:</label>
                            <span x-text="selectedProject?.client?.name"></span>
                        </div>
                        <div class="detail-item">
                            <label>Engagement Type:</label>
                            <span x-text="formatEngagementType(selectedProject?.engagement_type)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Period:</label>
                            <span x-text="formatDate(selectedProject?.engagement_period)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span x-text="formatStatus(selectedProject?.status)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Progress:</label>
                            <span x-text="(selectedProject?.progress_percentage || 0) + '%'"></span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Contact Information</h4>
                        <div class="detail-item">
                            <label>Contact Person:</label>
                            <span x-text="selectedProject?.contact_person"></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span x-text="selectedProject?.contact_email"></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span x-text="selectedProject?.contact_number"></span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Team Assignment</h4>
                        <div class="detail-item">
                            <label>Engagement Partner:</label>
                            <span x-text="selectedProject?.engagement_partner?.name || 'Not assigned'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Manager:</label>
                            <span x-text="selectedProject?.manager?.name || 'Not assigned'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Associate 1:</label>
                            <span x-text="selectedProject?.associate_1?.name || 'Not assigned'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Associate 2:</label>
                            <span x-text="selectedProject?.associate_2?.name || 'Not assigned'"></span>
                        </div>
                    </div>

                    <div class="detail-section" x-show="selectedProject?.notes">
                        <h4>Notes</h4>
                        <div class="detail-notes" x-text="selectedProject?.notes"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeDetailsModal()">
                    Close
                </button>
                @if(auth()->user()->hasPermission('edit_project'))
                <button type="button" class="btn btn-primary" @click="editProjectFromDetails()">
                    <i class="fas fa-edit"></i>
                    Edit Project
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Project Management Styles - Based on Client Management */
    .project-management-header {
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

    .btn-success {
        background: #10B981;
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
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
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

    .projects-card {
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

    .projects-table {
        width: 100%;
        border-collapse: collapse;
    }

    .projects-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
    }

    .projects-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: top;
    }

    .projects-table tbody tr:hover {
        background: #F9FAFB;
    }

    .projects-table tbody tr.selected {
        background: #EFF6FF;
    }

    .project-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .project-avatar {
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

    .project-details {
        min-width: 0;
    }

    .project-client {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .project-contact {
        font-size: 0.8rem;
        color: #6B7280;
        margin-bottom: 0.25rem;
    }

    .project-contact-email {
        font-size: 0.75rem;
        color: #9CA3AF;
    }

    .engagement-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .engagement-audit { background: #FEE2E2; color: #991B1B; }
    .engagement-accounting { background: #DBEAFE; color: #1E40AF; }
    .engagement-tax { background: #FEF3C7; color: #92400E; }
    .engagement-special-engagement { background: #F3E8FF; color: #6B21A8; }
    .engagement-others { background: #F3F4F6; color: #374151; }

    .team-info {
        font-size: 0.8rem;
    }

    .team-member {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .team-member i {
        width: 14px;
        color: #6B7280;
    }

    .team-associates {
        font-size: 0.75rem;
        color: #9CA3AF;
        font-style: italic;
    }

    .progress-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
    }

    .progress-bar {
        width: 60px;
        height: 8px;
        background: #E5E7EB;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10B981, #059669);
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 0.75rem;
        font-weight: 600;
        color: #374151;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-active { background: #D1FAE5; color: #065F46; }
    .status-completed { background: #DBEAFE; color: #1E40AF; }
    .status-on-hold { background: #FEF3C7; color: #92400E; }
    .status-cancelled { background: #FEE2E2; color: #991B1B; }

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
        flex-wrap: wrap;
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
        max-width: 1000px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .project-modal {
        max-width: 1200px;
    }

    .details-modal {
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
        max-height: calc(90vh - 140px);
        overflow-y: auto;
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

    .progress-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .progress-suffix {
        position: absolute;
        right: 1rem;
        color: #6B7280;
        font-weight: 500;
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
        min-width: 120px;
    }

    .detail-item span {
        color: #1F2937;
        text-align: right;
        max-width: 200px;
        word-wrap: break-word;
    }

    .detail-notes {
        color: #1F2937;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .project-management-header {
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

        .bulk-actions-buttons {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function projectManagement() {
        return {
            // Data
            projects: [],
            selectedProjects: [],
            availableClients: [],
            availableUsers: [],
            filters: {
                search: '',
                client_id: '',
                engagement_type: '',
                status: '',
                engagement_partner_id: '',
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
            projectForm: {
                client_id: '',
                engagement_type: '',
                engagement_period: '',
                contact_person: '',
                contact_email: '',
                contact_number: '',
                engagement_partner_id: '',
                manager_id: '',
                associate_1_id: '',
                associate_2_id: '',
                status: 'active',
                progress_percentage: 0,
                notes: ''
            },

            selectedProject: null,
            errors: {},

            // Initialize
            async init() {
                console.log('🚀 Project Management Init Starting');
                await this.loadSupportingData();
                await this.loadProjects();
            },

            // Load supporting data
            async loadSupportingData() {
                try {
                    // Load clients for dropdown
                    const clientsResponse = await fetch('/clients?per_page=100', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (clientsResponse.ok) {
                        const clientsResult = await clientsResponse.json();
                        this.availableClients = clientsResult.data || [];
                    }

                    // Load users for team assignments
                    const usersResponse = await fetch('/users?per_page=100', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (usersResponse.ok) {
                        const usersResult = await usersResponse.json();
                        this.availableUsers = usersResult.data || [];
                    }
                } catch (error) {
                    console.error('Failed to load supporting data:', error);
                }
            },

            // API calls
            async loadProjects(page = 1) {
                console.log('🔍 Loading projects - Start');
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        ...this.filters,
                        page: page
                    });

                    const url = `/projects?${params}`;
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
                        this.projects = result.data || [];
                        this.pagination = result.pagination || {};
                        console.log('✅ Projects loaded:', this.projects.length);
                    } else {
                        console.error('❌ Error:', result.message);
                        this.showError('Failed to load projects: ' + result.message);
                    }
                } catch (error) {
                    console.error('🚨 Network Error:', error);
                    this.showError('Failed to load projects: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async saveProject() {
                this.saving = true;
                this.errors = {};

                try {
                    // Clean up the form data before sending
                    const formData = { ...this.projectForm };

                    // Convert empty strings to null for nullable fields
                    const nullableFields = ['engagement_partner_id', 'manager_id', 'associate_1_id', 'associate_2_id', 'notes'];
                    nullableFields.forEach(field => {
                        if (formData[field] === '') {
                            formData[field] = null;
                        }
                    });

                    // Ensure progress_percentage is a number
                    if (formData.progress_percentage === '' || formData.progress_percentage === null) {
                        formData.progress_percentage = 0;
                    } else {
                        formData.progress_percentage = parseFloat(formData.progress_percentage);
                    }

                    const url = this.isEditing
                        ? `/projects/${formData.id}`
                        : '/projects';

                    const method = this.isEditing ? 'PUT' : 'POST';

                    console.log('Sending project data:', formData);

                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();
                    console.log('Server response:', result);

                    if (result.success) {
                        this.showSuccess(this.isEditing ? 'Project updated successfully' : 'Project created successfully');
                        this.closeModal();
                        await this.loadProjects();
                    } else {
                        if (result.errors) {
                            this.errors = result.errors;
                            console.log('Validation errors:', this.errors);
                        } else {
                            this.showError(result.message || 'Failed to save project');
                        }
                    }
                } catch (error) {
                    console.error('Network error:', error);
                    this.showError('Network error: ' + error.message);
                } finally {
                    this.saving = false;
                }
            },

            async deleteProject(project) {
                if (!confirm(`Are you sure you want to delete the project for ${project.client?.name}? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch(`/projects/${project.id}`, {
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
                        this.showSuccess('Project deleted successfully');
                        await this.loadProjects();
                    } else {
                        this.showError(result.message || 'Failed to delete project');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            // Modal methods
            openCreateModal() {
                this.isEditing = false;
                this.projectForm = {
                    client_id: '',
                    engagement_type: '',
                    engagement_period: '',
                    contact_person: '',
                    contact_email: '',
                    contact_number: '',
                    engagement_partner_id: '',
                    manager_id: '',
                    associate_1_id: '',
                    associate_2_id: '',
                    status: 'active',
                    progress_percentage: 0,
                    notes: ''
                };
                this.errors = {};
                this.showModal = true;
            },

            editProject(project) {
                this.isEditing = true;

                // Format the engagement period properly for the date input
                let formattedDate = '';
                if (project.engagement_period) {
                    // Handle different date formats
                    const date = new Date(project.engagement_period);
                    if (!isNaN(date.getTime())) {
                        formattedDate = date.toISOString().split('T')[0]; // YYYY-MM-DD format
                    }
                }

                this.projectForm = {
                    id: project.id,
                    client_id: project.client_id || '',
                    engagement_type: project.engagement_type || '',
                    engagement_period: formattedDate,
                    contact_person: project.contact_person || '',
                    contact_email: project.contact_email || '',
                    contact_number: project.contact_number || '',
                    engagement_partner_id: project.engagement_partner_id || '',
                    manager_id: project.manager_id || '',
                    associate_1_id: project.associate_1_id || '',
                    associate_2_id: project.associate_2_id || '',
                    status: project.status || 'active',
                    progress_percentage: project.progress_percentage || 0,
                    notes: project.notes || ''
                };
                this.errors = {};
                this.showModal = true;
            },

            viewProject(project) {
                this.selectedProject = project;
                this.showDetailsModal = true;
            },

            viewPbcRequests(project) {
                // Navigate to PBC requests page filtered by this project
                window.location.href = `/pbc-requests?project=${project.id}`;
            },

            editProjectFromDetails() {
                this.showDetailsModal = false;
                this.editProject(this.selectedProject);
            },

            closeModal() {
                this.showModal = false;
                this.isEditing = false;
                this.projectForm = {};
                this.errors = {};
            },

            closeDetailsModal() {
                this.showDetailsModal = false;
                this.selectedProject = null;
            },

            // Selection methods
            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedProjects = this.projects.map(project => project.id);
                } else {
                    this.selectedProjects = [];
                }
            },

            toggleProjectSelection(projectId) {
                const index = this.selectedProjects.indexOf(projectId);
                if (index > -1) {
                    this.selectedProjects.splice(index, 1);
                } else {
                    this.selectedProjects.push(projectId);
                }
            },

            clearSelection() {
                this.selectedProjects = [];
            },

            // Bulk actions
            async bulkUpdateStatus(status) {
                if (!confirm(`Update ${this.selectedProjects.length} selected projects to ${this.formatStatus(status)}?`)) return;
                this.showSuccess(`Projects updated to ${this.formatStatus(status)} successfully`);
                this.clearSelection();
                await this.loadProjects();
            },

            async bulkDelete() {
                if (!confirm(`Delete ${this.selectedProjects.length} selected projects? This action cannot be undone.`)) return;
                this.showSuccess('Projects deleted successfully');
                this.clearSelection();
                await this.loadProjects();
            },

            // Filter methods
            clearFilters() {
                this.filters = {
                    search: '',
                    client_id: '',
                    engagement_type: '',
                    status: '',
                    engagement_partner_id: '',
                    sort_by: 'created_at',
                    sort_order: 'desc',
                    per_page: 25
                };
                this.loadProjects();
            },

            async exportProjects() {
                try {
                    const params = new URLSearchParams(this.filters);
                    const response = await fetch(`/projects/export?${params}`, {
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
                        a.download = 'projects_export.xlsx';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                } catch (error) {
                    this.showError('Failed to export projects');
                }
            },

            // Pagination
            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadProjects(page);
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

            // Team helper methods
            getEngagementPartners() {
                return this.availableUsers.filter(user => user.role === 'engagement_partner');
            },

            getManagers() {
                return this.availableUsers.filter(user => user.role === 'manager');
            },

            getAssociates() {
                return this.availableUsers.filter(user => user.role === 'associate');
            },

            // Utility methods
            getProjectInitials(name) {
                return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            },

            getProjectAvatarColor(name) {
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

            getAssociatesCount(project) {
                let count = 0;
                if (project.associate_1) count++;
                if (project.associate_2) count++;
                return count;
            },

            formatEngagementType(type) {
                return type.split('_').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
            },

            formatStatus(status) {
                return status.split('_').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
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
