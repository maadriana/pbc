@extends('layouts.app')

@section('title', 'PBC Request Management')
@section('page-title', 'PBC Request Management')
@section('page-subtitle', 'Manage document requests, track submissions, and monitor progress')

@section('content')
<div x-data="pbcRequestManagement()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="pbc-management-header">
        <div class="header-title">
            <h2>PBC Request Checklist</h2>
            <p class="header-description">Track document requests, monitor submissions, and manage audit progress</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="exportRequests()" :disabled="loading">
                <i class="fas fa-download"></i>
                Export Report
            </button>
            @if(auth()->user()->hasPermission('create_pbc_request'))
            <button class="btn btn-success" @click="openCreateFromTemplateModal()" :disabled="loading">
                <i class="fas fa-clipboard-check"></i>
                Create from Template
            </button>
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Add Custom Request
            </button>
            @endif
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-cards" x-show="!loading">
        <div class="summary-card total">
            <div class="card-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.total || 0"></div>
                <div class="card-label">Total Requests</div>
            </div>
        </div>
        <div class="summary-card completed">
            <div class="card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.completed || 0"></div>
                <div class="card-label">Completed</div>
            </div>
        </div>
        <div class="summary-card pending">
            <div class="card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.pending || 0"></div>
                <div class="card-label">Active</div>
            </div>
        </div>
        <div class="summary-card overdue">
            <div class="card-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.overdue || 0"></div>
                <div class="card-label">Overdue</div>
            </div>
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Requests</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by title, client, or notes..."
                        x-model="filters.search"
                        @input.debounce.500ms="loadPbcRequests()"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Project</label>
                <select class="filter-select" x-model="filters.project_id" @change="loadPbcRequests()">
                    <option value="">All Projects</option>
                    <template x-for="project in availableProjects" :key="project.id">
                        <option :value="project.id" x-text="(project.client?.name || 'Unknown Client') + ' - ' + formatEngagementType(project.engagement_type)"></option>
                    </template>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Template</label>
                <select class="filter-select" x-model="filters.template_id" @change="loadPbcRequests()">
                    <option value="">All Templates</option>
                    <template x-for="template in availableTemplates" :key="template.id">
                        <option :value="template.id" x-text="template.name"></option>
                    </template>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.status" @change="loadPbcRequests()">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Assigned To</label>
                <select class="filter-select" x-model="filters.assigned_to" @change="loadPbcRequests()">
                    <option value="">All Assignees</option>
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
            <span>Loading PBC requests...</span>
        </div>
    </div>

    <!-- PBC REQUESTS TABLE -->
    <div class="pbc-requests-card" x-show="!loading">
        <div class="table-header">
            <div class="table-title">
                <h3>PBC Requests (<span x-text="pagination.total || 0"></span>)</h3>
            </div>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" @click="loadPbcRequests()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="pbc-requests-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" @change="toggleSelectAll($event)">
                        </th>
                        <th>Request Details</th>
                        <th>Project & Client</th>
                        <th>Template</th>
                        <th>Progress</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="request in pbcRequests" :key="request.id">
                        <tr :class="{ 'selected': selectedRequests.includes(request.id), 'overdue-row': isOverdue(request) }">
                            <td>
                                <input
                                    type="checkbox"
                                    :checked="selectedRequests.includes(request.id)"
                                    @change="toggleRequestSelection(request.id)"
                                >
                            </td>
                            <td>
                                <div class="request-info">
                                    <div class="request-title" x-text="request.title"></div>
                                    <div class="request-meta">
                                        <span class="meta-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            Created <span x-text="formatDate(request.created_at)"></span>
                                        </span>
                                        <span class="meta-item" x-show="request.notes">
                                            <i class="fas fa-sticky-note"></i>
                                            <span x-text="truncateText(request.notes, 30)"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="project-info">
                                    <div class="client-name" x-text="request.client_name"></div>
                                    <div class="project-details">
                                        <span x-text="formatEngagementType(request.project?.engagement_type)"></span>
                                        <span class="separator">•</span>
                                        <span x-text="request.audit_period"></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="template-info">
                                    <span class="template-name" x-text="request.template?.name || 'Custom'"></span>
                                    <div class="template-description" x-text="request.template?.description || 'Custom request'"></div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-info">
                                    <div class="progress-bar">
                                        <div class="progress-fill" :style="`width: ${request.completion_percentage || 0}%`"></div>
                                    </div>
                                    <div class="progress-text">
                                        <span x-text="Math.round(request.completion_percentage || 0)"></span>%
                                        (<span x-text="request.completed_items || 0"></span>/<span x-text="request.total_items || 0"></span>)
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-info" x-show="request.assignedTo">
                                    <div class="user-name" x-text="request.assignedTo?.name"></div>
                                    <div class="user-role" x-text="formatRole(request.assignedTo?.role)"></div>
                                </div>
                                <div x-show="!request.assignedTo" class="unassigned">Unassigned</div>
                            </td>
                            <td>
                                <div class="due-date-info" x-show="request.due_date">
                                    <span class="date-text" x-text="formatDate(request.due_date)"></span>
                                    <div class="due-indicator">
                                        <span :class="getDueBadgeClass(request)" x-text="getDueText(request)"></span>
                                    </div>
                                </div>
                                <div x-show="!request.due_date" class="no-due-date">No due date</div>
                            </td>
                            <td>
                                <span class="status-badge" :class="`status-${request.status}`" x-text="formatStatus(request.status)"></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-xs btn-secondary" @click="viewRequest(request)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    @if(auth()->user()->hasPermission('edit_pbc_request'))
                                    <button class="btn btn-xs btn-warning" @click="editRequest(request)" title="Edit Request">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <template x-if="request.status === 'draft' || request.status === 'active'">
                                        <button class="btn btn-xs btn-success" @click="completeRequest(request)" title="Mark as Complete">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </template>

                                    <template x-if="request.status === 'completed'">
                                        <button class="btn btn-xs btn-info" @click="reopenRequest(request)" title="Reopen Request">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </template>
                                    @endif

                                    <button class="btn btn-xs btn-primary" @click="sendReminder(request)" title="Send Reminder">
                                        <i class="fas fa-bell"></i>
                                    </button>

                                    @if(auth()->user()->hasPermission('create_pbc_request'))
                                    <button class="btn btn-xs btn-info" @click="duplicateRequest(request)" title="Duplicate Request">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('delete_pbc_request'))
                                    <button class="btn btn-xs btn-danger" @click="deleteRequest(request)" title="Delete Request">
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
            <div x-show="pbcRequests.length === 0 && !loading" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>No PBC requests found</h3>
                <p>Try adjusting your search criteria or create a new request.</p>
                @if(auth()->user()->hasPermission('create_pbc_request'))
                <div class="empty-actions">
                    <button class="btn btn-primary" @click="openCreateFromTemplateModal()">
                        <i class="fas fa-clipboard-check"></i>
                        Create from Template
                    </button>
                    <button class="btn btn-secondary" @click="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Custom Request
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- PAGINATION -->
        <div class="table-pagination" x-show="pbcRequests.length > 0">
            <div class="pagination-info">
                Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                of <span x-text="pagination.total || 0"></span> requests
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
    <div class="bulk-actions" x-show="selectedRequests.length > 0" x-transition>
        <div class="bulk-actions-content">
            <span class="selected-count"><span x-text="selectedRequests.length"></span> requests selected</span>
            <div class="bulk-actions-buttons">
                @if(auth()->user()->hasPermission('edit_pbc_request'))
                <button class="btn btn-sm btn-success" @click="bulkComplete()">
                    <i class="fas fa-check"></i>
                    Complete
                </button>
                <button class="btn btn-sm btn-info" @click="bulkReopen()">
                    <i class="fas fa-undo"></i>
                    Reopen
                </button>
                <button class="btn btn-sm btn-warning" @click="bulkAssign()">
                    <i class="fas fa-user"></i>
                    Reassign
                </button>
                @endif
                <button class="btn btn-sm btn-primary" @click="bulkSendReminders()">
                    <i class="fas fa-bell"></i>
                    Send Reminders
                </button>
                @if(auth()->user()->hasPermission('delete_pbc_request'))
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

    <!-- CREATE FROM TEMPLATE MODAL -->
    <div class="modal-overlay" x-show="showTemplateModal" x-transition @click="closeTemplateModal()">
        <div class="modal template-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Create PBC Request from Template</h3>
                <button class="modal-close" @click="closeTemplateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="createFromTemplate()">
                <div class="modal-body">
                    <div class="form-section">
                        <h4 class="form-section-title">Project & Template Selection</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Project *</label>
                                <select class="form-select" x-model="templateForm.project_id" required @change="loadCompatibleTemplates()" :class="{ 'error': errors.project_id }">
                                    <option value="">Select Project</option>
                                    <template x-for="project in availableProjects" :key="project.id">
                                        <option :value="project.id" x-text="(project.client?.name || 'Unknown Client') + ' - ' + formatEngagementType(project.engagement_type)"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.project_id" x-text="errors.project_id"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Template *</label>
                                <select class="form-select" x-model="templateForm.template_id" required :class="{ 'error': errors.template_id }">
                                    <option value="">Select Template</option>
                                    <template x-for="template in compatibleTemplates" :key="template.id">
                                        <option :value="template.id" x-text="template.name + ' - ' + (template.description || 'No description')"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.template_id" x-text="errors.template_id"></div>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Title (Optional)</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="templateForm.title"
                                    placeholder="Will be auto-generated if left empty"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Assignment & Timeline</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" x-model="templateForm.assigned_to">
                                    <option value="">Select Assignee</option>
                                    <template x-for="user in availableUsers" :key="user.id">
                                        <option :value="user.id" x-text="user.name + ' (' + formatRole(user.role) + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    x-model="templateForm.due_date"
                                    :min="new Date().toISOString().split('T')[0]"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeTemplateModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving">Create Request</span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin"></i>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CUSTOM REQUEST MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal custom-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEditing ? 'Edit PBC Request' : 'Create Custom PBC Request'"></h3>
                <button class="modal-close" @click="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveRequest()">
                <div class="modal-body">
                    <div class="form-section">
                        <h4 class="form-section-title">Basic Information</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Project *</label>
                                <select class="form-select" x-model="requestForm.project_id" required :class="{ 'error': errors.project_id }">
                                    <option value="">Select Project</option>
                                    <template x-for="project in availableProjects" :key="project.id">
                                        <option :value="project.id" x-text="(project.client?.name || 'Unknown Client') + ' - ' + formatEngagementType(project.engagement_type)"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.project_id" x-text="errors.project_id"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Template</label>
                                <select class="form-select" x-model="requestForm.template_id">
                                    <option value="">None (Custom Request)</option>
                                    <template x-for="template in availableTemplates" :key="template.id">
                                        <option :value="template.id" x-text="template.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Title *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="requestForm.title"
                                    required
                                    placeholder="Enter request title"
                                    :class="{ 'error': errors.title }"
                                >
                                <div class="form-error" x-show="errors.title" x-text="errors.title"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Assignment & Timeline</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" x-model="requestForm.assigned_to">
                                    <option value="">Select Assignee</option>
                                    <template x-for="user in availableUsers" :key="user.id">
                                        <option :value="user.id" x-text="user.name + ' (' + formatRole(user.role) + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    x-model="requestForm.due_date"
                                    :min="new Date().toISOString().split('T')[0]"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Additional Information</h4>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea
                                class="form-textarea"
                                x-model="requestForm.notes"
                                placeholder="Enter additional notes or instructions"
                                rows="3"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving" x-text="isEditing ? 'Update Request' : 'Create Request'"></span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span x-text="isEditing ? 'Updating...' : 'Creating...'"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- REQUEST DETAILS MODAL -->
    <div class="modal-overlay" x-show="showDetailsModal" x-transition @click="closeDetailsModal()">
        <div class="modal details-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">PBC Request Details</h3>
                <button class="modal-close" @click="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body" x-show="selectedRequest">
                <div class="details-grid">
                    <div class="detail-section">
                        <h4>Request Information</h4>
                        <div class="detail-item">
                            <label>Title:</label>
                            <span x-text="selectedRequest?.title"></span>
                        </div>
                        <div class="detail-item">
                            <label>Template:</label>
                            <span x-text="selectedRequest?.template?.name || 'Custom Request'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge" :class="`status-${selectedRequest?.status}`" x-text="formatStatus(selectedRequest?.status)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Progress:</label>
                            <span x-text="Math.round(selectedRequest?.completion_percentage || 0) + '% (' + (selectedRequest?.completed_items || 0) + '/' + (selectedRequest?.total_items || 0) + ')'"></span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Project & Timeline</h4>
                        <div class="detail-item">
                            <label>Client:</label>
                            <span x-text="selectedRequest?.client_name"></span>
                        </div>
                        <div class="detail-item">
                            <label>Engagement:</label>
                            <span x-text="formatEngagementType(selectedRequest?.project?.engagement_type)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Period:</label>
                            <span x-text="selectedRequest?.audit_period"></span>
                        </div>
                        <div class="detail-item">
                            <label>Assigned To:</label>
                            <span x-text="selectedRequest?.assignedTo?.name || 'Unassigned'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Due Date:</label>
                            <span x-text="formatDate(selectedRequest?.due_date) || 'No due date'"></span>
                        </div>
                    </div>

                    <div class="detail-section full-width" x-show="selectedRequest?.notes">
                        <h4>Notes</h4>
                        <div class="detail-notes" x-text="selectedRequest?.notes"></div>
                    </div>

                    <div class="detail-section full-width">
                        <h4>Recent Activity</h4>
                        <div class="activity-list">
                            <div class="activity-item">
                                <i class="fas fa-plus-circle"></i>
                                <span>Request created on <span x-text="formatDate(selectedRequest?.created_at)"></span></span>
                            </div>
                            <div class="activity-item" x-show="selectedRequest?.completed_at">
                                <i class="fas fa-check-circle"></i>
                                <span>Completed on <span x-text="formatDate(selectedRequest?.completed_at)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeDetailsModal()">
                    Close
                </button>
                @if(auth()->user()->hasPermission('edit_pbc_request'))
                <button type="button" class="btn btn-primary" @click="editRequestFromDetails()">
                    <i class="fas fa-edit"></i>
                    Edit Request
                </button>
                @endif
                <button type="button" class="btn btn-info" @click="viewRequestItems()">
                    <i class="fas fa-list"></i>
                    View Items
                </button>
            </div>
        </div>
    </div>

    <!-- BULK ASSIGN MODAL -->
    <div class="modal-overlay" x-show="showBulkAssignModal" x-transition @click="closeBulkAssignModal()">
        <div class="modal small-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Bulk Assign Requests</h3>
                <button class="modal-close" @click="closeBulkAssignModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Assign <span x-text="selectedRequests.length"></span> requests to:</label>
                    <select class="form-select" x-model="bulkAssignUserId" required>
                        <option value="">Select User</option>
                        <template x-for="user in availableUsers" :key="user.id">
                            <option :value="user.id" x-text="user.name + ' (' + formatRole(user.role) + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeBulkAssignModal()">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" @click="performBulkAssign()" :disabled="!bulkAssignUserId">
                    <i class="fas fa-user"></i>
                    Assign Requests
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* PBC Request Management Styles */
    .pbc-management-header {
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

    /* Summary Cards */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .summary-card.total::before { background: #3B82F6; }
    .summary-card.completed::before { background: #10B981; }
    .summary-card.pending::before { background: #F59E0B; }
    .summary-card.overdue::before { background: #EF4444; }

    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }

    .summary-card.total .card-icon { background: #3B82F6; }
    .summary-card.completed .card-icon { background: #10B981; }
    .summary-card.pending .card-icon { background: #F59E0B; }
    .summary-card.overdue .card-icon { background: #EF4444; }

    .card-content {
        flex: 1;
    }

    .card-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1F2937;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .card-label {
        font-size: 0.9rem;
        color: #6B7280;
        font-weight: 500;
    }

    /* Buttons */
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

    .btn-success {
        background: #10B981;
        color: white;
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

    /* Filters Section */
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

    /* Loading */
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

    /* Table */
    .pbc-requests-card {
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

    .pbc-requests-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    .pbc-requests-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
        white-space: nowrap;
    }

    .pbc-requests-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: top;
    }

    .pbc-requests-table tbody tr:hover {
        background: #F9FAFB;
    }

    .pbc-requests-table tbody tr.selected {
        background: #EFF6FF;
    }

    .pbc-requests-table tbody tr.overdue-row {
        background: #FEF2F2;
    }

    /* Table Cell Content */
    .request-info {
        min-width: 250px;
    }

    .request-title {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }

    .request-meta {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .meta-item {
        font-size: 0.75rem;
        color: #6B7280;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .meta-item i {
        width: 12px;
        color: #9CA3AF;
    }

    .project-info {
        min-width: 200px;
    }

    .client-name {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .project-details {
        font-size: 0.8rem;
        color: #6B7280;
    }

    .separator {
        margin: 0 0.5rem;
        color: #9CA3AF;
    }

    .template-info {
        min-width: 150px;
    }

    .template-name {
        font-weight: 500;
        color: #1F2937;
        display: block;
        margin-bottom: 0.25rem;
    }

    .template-description {
        font-size: 0.75rem;
        color: #6B7280;
        line-height: 1.3;
    }

    .progress-info {
        min-width: 120px;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #F3F4F6;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10B981, #059669);
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 0.8rem;
        color: #6B7280;
        text-align: center;
    }

    .user-info {
        min-width: 120px;
    }

    .user-name {
        font-weight: 500;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .user-role {
        font-size: 0.75rem;
        color: #6B7280;
        text-transform: capitalize;
    }

    .unassigned, .no-due-date {
        font-style: italic;
        color: #9CA3AF;
        font-size: 0.8rem;
    }

    .due-date-info {
        min-width: 100px;
    }

    .date-text {
        font-size: 0.8rem;
        color: #6B7280;
        display: block;
        margin-bottom: 0.25rem;
    }

    .due-indicator {
        font-size: 0.75rem;
    }

    .due-soon {
        color: #F59E0B;
        font-weight: 600;
    }

    .due-overdue {
        color: #EF4444;
        font-weight: 600;
    }

    .due-ok {
        color: #10B981;
        font-weight: 500;
    }

    /* Status badges */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        text-align: center;
    }

    .status-draft { background: #F3F4F6; color: #6B7280; }
    .status-active { background: #DBEAFE; color: #1E40AF; }
    .status-completed { background: #D1FAE5; color: #065F46; }
    .status-cancelled { background: #FEE2E2; color: #991B1B; }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        min-width: 200px;
    }

    /* Empty State */
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

    .empty-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    /* Pagination */
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

    /* Bulk Actions */
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

    /* Modal Styles */
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
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .template-modal, .custom-modal {
        max-width: 800px;
    }

    .details-modal {
        max-width: 900px;
    }

    .small-modal {
        max-width: 500px;
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

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    /* Details Grid */
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

    .detail-section.full-width {
        grid-column: 1 / -1;
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

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #F9FAFB;
        border-radius: 8px;
        border-left: 4px solid #3B82F6;
    }

    .activity-item i {
        color: #3B82F6;
        width: 16px;
        text-align: center;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .filters-grid {
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
        }

        .filters-grid .filter-group:nth-child(5) {
            grid-column: span 2;
        }

        .filters-grid .filter-group:nth-child(6) {
            grid-column: span 4;
            justify-self: center;
        }
    }

    @media (max-width: 768px) {
        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .pbc-management-header {
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

    @media (max-width: 480px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function pbcRequestManagement() {
        return {
            // Data
            pbcRequests: [],
            selectedRequests: [],
            availableProjects: [],
            availableTemplates: [],
            compatibleTemplates: [],
            availableUsers: [],
            stats: {
                total: 0,
                completed: 0,
                pending: 0,
                overdue: 0
            },
            filters: {
                search: '',
                project_id: '',
                template_id: '',
                status: '',
                assigned_to: '',
                sort_by: 'created_at',
                sort_order: 'desc',
                per_page: 25
            },
            pagination: {},
            loading: false,

            // Modal states
            showModal: false,
            showTemplateModal: false,
            showDetailsModal: false,
            showBulkAssignModal: false,
            isEditing: false,
            saving: false,

            // Form data
            requestForm: {
                project_id: '',
                template_id: '',
                title: '',
                assigned_to: '',
                due_date: '',
                notes: ''
            },
            templateForm: {
                project_id: '',
                template_id: '',
                title: '',
                assigned_to: '',
                due_date: ''
            },

            selectedRequest: null,
            errors: {},
            bulkAssignUserId: '',

            // Initialize
            async init() {
                console.log('🚀 PBC Request Management Init Starting');
                await this.loadSupportingData();
                await this.loadPbcRequests();
                await this.loadStats();
            },

            // Helper method to get default fetch options with CSRF and proper headers
            getFetchOptions(method = 'GET', data = null) {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const options = {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || ''
                    },
                    credentials: 'same-origin'
                };

                if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
                    options.body = JSON.stringify(data);
            }

            return options;
        },

        // Load supporting data
        async loadSupportingData() {
            try {
                console.log('📊 Loading supporting data...');

                const responses = await Promise.all([
                    fetch('/api/v1/projects', this.getFetchOptions()),
                    fetch('/api/v1/pbc-requests/available-templates', this.getFetchOptions()),
                    fetch('/api/v1/users', this.getFetchOptions())
                ]);

                const [projectsRes, templatesRes, usersRes] = responses;

                if (projectsRes.ok) {
                    const projectsData = await projectsRes.json();
                    this.availableProjects = projectsData.data || [];
                    console.log('✅ Projects loaded:', this.availableProjects.length);
                }

                if (templatesRes.ok) {
                    const templatesData = await templatesRes.json();
                    this.availableTemplates = templatesData.data || [];
                    console.log('✅ Templates loaded:', this.availableTemplates.length);
                }

                if (usersRes.ok) {
                    const usersData = await usersRes.json();
                    this.availableUsers = usersData.data || [];
                    console.log('✅ Users loaded:', this.availableUsers.length);
                }

            } catch (error) {
                console.error('❌ Error loading supporting data:', error);
                this.showAlert('Failed to load supporting data', 'error');
            }
        },

        // Load PBC requests
        async loadPbcRequests(page = 1) {
            try {
                this.loading = true;
                console.log('📋 Loading PBC requests...', { page, filters: this.filters });

                const params = new URLSearchParams({
                    page: page.toString(),
                    per_page: this.filters.per_page.toString(),
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([key, value]) => value && key !== 'per_page')
                    )
                });

                const response = await fetch(`/api/v1/pbc-requests?${params}`, this.getFetchOptions());

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('✅ PBC requests loaded:', data);

                this.pbcRequests = data.data || [];
                this.pagination = data.pagination || {};

                // Update stats based on loaded data
                this.updateStats();

            } catch (error) {
                console.error('❌ Error loading PBC requests:', error);
                this.showAlert('Failed to load PBC requests', 'error');
                this.pbcRequests = [];
            } finally {
                this.loading = false;
            }
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/api/v1/dashboard/stats', this.getFetchOptions());

                if (response.ok) {
                    const data = await response.json();
                    console.log('📊 Stats loaded:', data);

                    // Extract PBC-specific stats
                    this.stats = {
                        total: data.data?.pbc_requests?.total || 0,
                        completed: data.data?.pbc_requests?.completed || 0,
                        pending: data.data?.pbc_requests?.active || 0,
                        overdue: data.data?.pbc_requests?.overdue || 0
                    };
                }
            } catch (error) {
                console.error('❌ Error loading stats:', error);
                // Fallback to local calculation
                this.updateStats();
            }
        },

        // Update stats from current data
        updateStats() {
            if (!this.pbcRequests.length) return;

            this.stats = {
                total: this.pbcRequests.length,
                completed: this.pbcRequests.filter(r => r.status === 'completed').length,
                pending: this.pbcRequests.filter(r => r.status === 'active').length,
                overdue: this.pbcRequests.filter(r => this.isOverdue(r)).length
            };
        },

        // Load compatible templates for selected project
        async loadCompatibleTemplates() {
            if (!this.templateForm.project_id) {
                this.compatibleTemplates = [];
                return;
            }

            try {
                const project = this.availableProjects.find(p => p.id == this.templateForm.project_id);
                if (!project) return;

                const params = new URLSearchParams({
                    engagement_type: project.engagement_type,
                    project_id: project.id
                });

                const response = await fetch(`/api/v1/pbc-requests/available-templates?${params}`, this.getFetchOptions());

                if (response.ok) {
                    const data = await response.json();
                    this.compatibleTemplates = data.data || [];
                }
            } catch (error) {
                console.error('❌ Error loading compatible templates:', error);
                this.compatibleTemplates = this.availableTemplates;
            }
        },

        // Create from template
        async createFromTemplate() {
            try {
                this.saving = true;
                this.errors = {};

                console.log('📝 Creating from template:', this.templateForm);

                const response = await fetch('/api/v1/pbc-requests/create-from-template',
                    this.getFetchOptions('POST', this.templateForm));

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        this.errors = data.errors;
                    }
                    throw new Error(data.message || 'Failed to create request');
                }

                console.log('✅ Request created from template:', data);
                this.showAlert('PBC request created successfully!', 'success');
                this.closeTemplateModal();
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error creating from template:', error);
                this.showAlert(error.message || 'Failed to create request', 'error');
            } finally {
                this.saving = false;
            }
        },

        // Create/Update custom request
        async saveRequest() {
            try {
                this.saving = true;
                this.errors = {};

                const url = this.isEditing
                    ? `/api/v1/pbc-requests/${this.selectedRequest.id}`
                    : '/api/v1/pbc-requests';
                const method = this.isEditing ? 'PUT' : 'POST';

                console.log(`📝 ${this.isEditing ? 'Updating' : 'Creating'} request:`, this.requestForm);

                const response = await fetch(url, this.getFetchOptions(method, this.requestForm));
                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        this.errors = data.errors;
                    }
                    throw new Error(data.message || 'Failed to save request');
                }

                console.log('✅ Request saved:', data);
                this.showAlert(`PBC request ${this.isEditing ? 'updated' : 'created'} successfully!`, 'success');
                this.closeModal();
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error saving request:', error);
                this.showAlert(error.message || 'Failed to save request', 'error');
            } finally {
                this.saving = false;
            }
        },

        // Complete request
        async completeRequest(request) {
            if (!confirm(`Mark "${request.title}" as completed?`)) return;

            try {
                const response = await fetch(`/api/v1/pbc-requests/${request.id}/complete`,
                    this.getFetchOptions('POST'));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to complete request');
                }

                console.log('✅ Request completed:', request.title);
                this.showAlert('Request marked as completed!', 'success');
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error completing request:', error);
                this.showAlert(error.message || 'Failed to complete request', 'error');
            }
        },

        // Reopen request
        async reopenRequest(request) {
            if (!confirm(`Reopen "${request.title}"?`)) return;

            try {
                const response = await fetch(`/api/v1/pbc-requests/${request.id}/reopen`,
                    this.getFetchOptions('POST'));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to reopen request');
                }

                console.log('✅ Request reopened:', request.title);
                this.showAlert('Request reopened successfully!', 'success');
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error reopening request:', error);
                this.showAlert(error.message || 'Failed to reopen request', 'error');
            }
        },

        // Delete request
        async deleteRequest(request) {
            if (!confirm(`Delete "${request.title}"? This action cannot be undone.`)) return;

            try {
                const response = await fetch(`/api/v1/pbc-requests/${request.id}`,
                    this.getFetchOptions('DELETE'));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to delete request');
                }

                console.log('✅ Request deleted:', request.title);
                this.showAlert('Request deleted successfully!', 'success');
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error deleting request:', error);
                this.showAlert(error.message || 'Failed to delete request', 'error');
            }
        },

        // Duplicate request
        async duplicateRequest(request) {
            try {
                const response = await fetch(`/api/v1/pbc-requests/${request.id}/duplicate`,
                    this.getFetchOptions('POST'));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to duplicate request');
                }

                console.log('✅ Request duplicated:', request.title);
                this.showAlert('Request duplicated successfully!', 'success');
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error duplicating request:', error);
                this.showAlert(error.message || 'Failed to duplicate request', 'error');
            }
        },

        // Send reminder
        async sendReminder(request) {
            if (!request.assignedTo) {
                this.showAlert('Cannot send reminder - no one assigned to this request', 'warning');
                return;
            }

            try {
                const reminderData = {
                    remindable_type: 'App\\Models\\PbcRequest',
                    remindable_id: request.id,
                    subject: `Reminder: ${request.title}`,
                    message: `This is a reminder about your pending PBC request: ${request.title}`,
                    type: 'follow_up',
                    method: 'email',
                    sent_to: request.assignedTo.id,
                    scheduled_at: new Date().toISOString()
                };

                const response = await fetch('/api/v1/pbc-reminders',
                    this.getFetchOptions('POST', reminderData));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to send reminder');
                }

                console.log('✅ Reminder sent for:', request.title);
                this.showAlert('Reminder sent successfully!', 'success');

            } catch (error) {
                console.error('❌ Error sending reminder:', error);
                this.showAlert(error.message || 'Failed to send reminder', 'error');
            }
        },

        // Bulk operations
        async bulkComplete() {
            if (!this.selectedRequests.length) return;
            if (!confirm(`Mark ${this.selectedRequests.length} requests as completed?`)) return;

            try {
                const response = await fetch('/api/v1/pbc-requests/bulk-update',
                    this.getFetchOptions('POST', {
                        request_ids: this.selectedRequests,
                        action: 'complete'
                    }));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to complete requests');
                }

                const result = await response.json();
                console.log('✅ Bulk complete result:', result);
                this.showAlert(`${result.data.success} requests completed successfully!`, 'success');
                this.clearSelection();
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error bulk completing:', error);
                this.showAlert(error.message || 'Failed to complete requests', 'error');
            }
        },

        async bulkReopen() {
            if (!this.selectedRequests.length) return;
            if (!confirm(`Reopen ${this.selectedRequests.length} requests?`)) return;

            try {
                const response = await fetch('/api/v1/pbc-requests/bulk-update',
                    this.getFetchOptions('POST', {
                        request_ids: this.selectedRequests,
                        action: 'reopen'
                    }));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to reopen requests');
                }

                const result = await response.json();
                console.log('✅ Bulk reopen result:', result);
                this.showAlert(`${result.data.success} requests reopened successfully!`, 'success');
                this.clearSelection();
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error bulk reopening:', error);
                this.showAlert(error.message || 'Failed to reopen requests', 'error');
            }
        },

        async bulkDelete() {
            if (!this.selectedRequests.length) return;
            if (!confirm(`Delete ${this.selectedRequests.length} requests? This action cannot be undone.`)) return;

            try {
                const response = await fetch('/api/v1/pbc-requests/bulk-update',
                    this.getFetchOptions('POST', {
                        request_ids: this.selectedRequests,
                        action: 'delete'
                    }));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to delete requests');
                }

                const result = await response.json();
                console.log('✅ Bulk delete result:', result);
                this.showAlert(`${result.data.success} requests deleted successfully!`, 'success');
                this.clearSelection();
                await this.loadPbcRequests();
                await this.loadStats();

            } catch (error) {
                console.error('❌ Error bulk deleting:', error);
                this.showAlert(error.message || 'Failed to delete requests', 'error');
            }
        },

        bulkAssign() {
            if (!this.selectedRequests.length) return;
            this.showBulkAssignModal = true;
            this.bulkAssignUserId = '';
        },

        async performBulkAssign() {
            if (!this.bulkAssignUserId) return;

            try {
                const response = await fetch('/api/v1/pbc-requests/bulk-update',
                    this.getFetchOptions('POST', {
                        request_ids: this.selectedRequests,
                        action: 'assign',
                        data: { assigned_to: this.bulkAssignUserId }
                    }));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to assign requests');
                }

                const result = await response.json();
                console.log('✅ Bulk assign result:', result);
                this.showAlert(`${result.data.success} requests assigned successfully!`, 'success');
                this.closeBulkAssignModal();
                this.clearSelection();
                await this.loadPbcRequests();

            } catch (error) {
                console.error('❌ Error bulk assigning:', error);
                this.showAlert(error.message || 'Failed to assign requests', 'error');
            }
        },

        async bulkSendReminders() {
            if (!this.selectedRequests.length) return;
            if (!confirm(`Send reminders for ${this.selectedRequests.length} requests?`)) return;

            try {
                const response = await fetch('/api/v1/pbc-reminders/bulk-send',
                    this.getFetchOptions('POST', {
                        pbc_request_ids: this.selectedRequests,
                        type: 'follow_up',
                        custom_message: 'This is a reminder about your pending PBC request. Please review and submit the required documents.'
                    }));

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to send reminders');
                }

                const result = await response.json();
                console.log('✅ Bulk reminders result:', result);
                this.showAlert(`${result.data.success} reminders sent successfully!`, 'success');
                this.clearSelection();

            } catch (error) {
                console.error('❌ Error sending bulk reminders:', error);
                this.showAlert(error.message || 'Failed to send reminders', 'error');
            }
        },

        // Export requests
        async exportRequests() {
            try {
                const params = new URLSearchParams(
                    Object.fromEntries(
                        Object.entries(this.filters).filter(([key, value]) => value)
                    )
                );

                const response = await fetch(`/api/v1/pbc-requests/export?${params}`, this.getFetchOptions());

                if (!response.ok) {
                    throw new Error('Failed to export requests');
                }

                // Handle file download
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `pbc-requests-${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                this.showAlert('Export completed successfully!', 'success');

            } catch (error) {
                console.error('❌ Error exporting:', error);
                this.showAlert('Failed to export requests', 'error');
            }
        },

        // Modal management
        openCreateModal() {
            this.isEditing = false;
            this.requestForm = {
                project_id: '',
                template_id: '',
                title: '',
                assigned_to: '',
                due_date: '',
                notes: ''
            };
            this.errors = {};
            this.showModal = true;
        },

        openCreateFromTemplateModal() {
            this.templateForm = {
                project_id: '',
                template_id: '',
                title: '',
                assigned_to: '',
                due_date: ''
            };
            this.compatibleTemplates = [];
            this.errors = {};
            this.showTemplateModal = true;
        },

        editRequest(request) {
            this.isEditing = true;
            this.selectedRequest = request;
            this.requestForm = {
                project_id: request.project_id || '',
                template_id: request.template_id || '',
                title: request.title || '',
                assigned_to: request.assigned_to || '',
                due_date: request.due_date || '',
                notes: request.notes || ''
            };
            this.errors = {};
            this.showModal = true;
        },

        editRequestFromDetails() {
            this.closeDetailsModal();
            this.editRequest(this.selectedRequest);
        },

        viewRequest(request) {
            this.selectedRequest = request;
            this.showDetailsModal = true;
        },

        viewRequestItems() {
            if (this.selectedRequest) {
                window.location.href = `/pbc-requests/${this.selectedRequest.id}/items`;
            }
        },

        closeModal() {
            this.showModal = false;
            this.isEditing = false;
            this.errors = {};
            this.saving = false;
        },

        closeTemplateModal() {
            this.showTemplateModal = false;
            this.errors = {};
            this.saving = false;
        },

        closeDetailsModal() {
            this.showDetailsModal = false;
            this.selectedRequest = null;
        },

        closeBulkAssignModal() {
            this.showBulkAssignModal = false;
            this.bulkAssignUserId = '';
        },

        // Selection management
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedRequests = this.pbcRequests.map(r => r.id);
            } else {
                this.selectedRequests = [];
            }
        },

        toggleRequestSelection(requestId) {
            const index = this.selectedRequests.indexOf(requestId);
            if (index > -1) {
                this.selectedRequests.splice(index, 1);
            } else {
                this.selectedRequests.push(requestId);
            }
        },

        clearSelection() {
            this.selectedRequests = [];
        },

        // Pagination
        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadPbcRequests(page);
            }
        },

        get visiblePages() {
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const pages = [];

            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        // Filters
        clearFilters() {
            this.filters = {
                search: '',
                project_id: '',
                template_id: '',
                status: '',
                assigned_to: '',
                sort_by: 'created_at',
                sort_order: 'desc',
                per_page: 25
            };
            this.loadPbcRequests();
        },

        // Utility functions
        formatDate(dateString) {
            if (!dateString) return '';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch {
                return dateString;
            }
        },

        formatEngagementType(type) {
            if (!type) return '';
            return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatRole(role) {
            if (!role) return '';
            return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatStatus(status) {
            if (!status) return '';
            return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        },

        isOverdue(request) {
            if (!request.due_date || request.status === 'completed') return false;
            return new Date(request.due_date) < new Date();
        },

        getDueBadgeClass(request) {
            if (!request.due_date || request.status === 'completed') return '';

            const dueDate = new Date(request.due_date);
            const now = new Date();
            const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));

            if (diffDays < 0) return 'due-overdue';
            if (diffDays <= 3) return 'due-soon';
            return 'due-ok';
        },

        getDueText(request) {
            if (!request.due_date || request.status === 'completed') return '';

            const dueDate = new Date(request.due_date);
            const now = new Date();
            const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));

            if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
            if (diffDays === 0) return 'Due today';
            if (diffDays === 1) return 'Due tomorrow';
            if (diffDays <= 7) return `${diffDays} days left`;
            return '';
        },

        // Alert system
        showAlert(message, type = 'info') {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <div class="alert-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

            // Add to page
            document.body.appendChild(alert);

            // Add show class for animation
            setTimeout(() => alert.classList.add('show'), 100);

            // Remove after delay
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }, 4000);
        }
    }
}
</script>

<!-- Alert Styles -->
<style>
    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease, opacity 0.3s ease;
        opacity: 0;
    }

    .alert.show {
        transform: translateX(0);
        opacity: 1;
    }

    .alert-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-content i {
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .alert-success {
        background: #D1FAE5;
        color: #065F46;
        border-left: 4px solid #10B981;
    }

    .alert-error {
        background: #FEE2E2;
        color: #991B1B;
        border-left: 4px solid #EF4444;
    }

    .alert-warning {
        background: #FEF3C7;
        color: #92400E;
        border-left: 4px solid #F59E0B;
    }

    .alert-info {
        background: #DBEAFE;
        color: #1E40AF;
        border-left: 4px solid #3B82F6;
    }
</style>
@endpush

@endsection
