@extends('layouts.app')

@section('title', 'PBC Request Management')
@section('page-title', 'PBC Request Management')
@section('page-subtitle', 'Manage audit document requests, track submissions, and monitor progress')

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
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Add Request
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
                <div class="card-label">Pending</div>
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
                        placeholder="Search by title, description, or client..."
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
                <label class="filter-label">Category</label>
                <select class="filter-select" x-model="filters.category_id" @change="loadPbcRequests()">
                    <option value="">All Categories</option>
                    <template x-for="category in availableCategories" :key="category.id">
                        <option :value="category.id" x-text="category.name"></option>
                    </template>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.status" @change="loadPbcRequests()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Priority</label>
                <select class="filter-select" x-model="filters.priority" @change="loadPbcRequests()">
                    <option value="">All Priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Assigned To</label>
                <select class="filter-select" x-model="filters.assigned_to_id" @change="loadPbcRequests()">
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
                        <th>Category</th>
                        <th>Request Description</th>
                        <th>Requestor</th>
                        <th>Date Requested</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Notes</th>
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
                                <div class="category-info">
                                    <span class="category-badge"
                                          :style="`background: ${request.category?.color_code || '#6B7280'}20; color: ${request.category?.color_code || '#6B7280'}`"
                                          x-text="request.category?.code || 'N/A'"></span>
                                    <div class="category-name" x-text="request.category?.name || 'Uncategorized'"></div>
                                </div>
                            </td>
                            <td>
                                <div class="request-info">
                                    <div class="request-title" x-text="request.title"></div>
                                    <div class="request-description" x-text="truncateText(request.description, 80)"></div>
                                    <div class="request-project" x-text="'Project: ' + (request.project?.client?.name || 'Unknown')"></div>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-name" x-text="request.requestor?.name || 'Unknown'"></div>
                                    <div class="user-role" x-text="formatRole(request.requestor?.role || '')"></div>
                                </div>
                            </td>
                            <td>
                                <span class="date-text" x-text="formatDate(request.date_requested)"></span>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-name" x-text="request.assigned_to?.name || 'Unassigned'"></div>
                                    <div class="user-role" x-text="formatRole(request.assigned_to?.role || '')"></div>
                                </div>
                            </td>
                            <td>
                                <div class="due-date-info">
                                    <span class="date-text" x-text="formatDate(request.due_date)"></span>
                                    <div class="due-indicator" x-show="getDaysUntilDue(request) !== null">
                                        <span :class="getDueBadgeClass(request)" x-text="getDueText(request)"></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="status-priority">
                                    <span class="status-badge" :class="`status-${request.status?.replace('_', '-')}`"
                                          x-text="formatStatus(request.status)"></span>
                                    <span class="priority-badge" :class="`priority-${request.priority}`"
                                          x-text="formatPriority(request.priority)"></span>
                                </div>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    @if(auth()->user()->hasPermission('view_pbc_request'))
                                    <button class="btn btn-xs btn-secondary" @click="viewRequest(request)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('edit_pbc_request'))
                                    <button class="btn btn-xs btn-warning" @click="editRequest(request)" title="Edit Request">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <template x-if="request.status === 'pending' || request.status === 'in_progress'">
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

                                    @if(auth()->user()->hasPermission('delete_pbc_request'))
                                    <button class="btn btn-xs btn-danger" @click="deleteRequest(request)" title="Delete Request">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="notes-cell">
                                    <span x-text="truncateText(request.notes || '', 50)" :title="request.notes"></span>
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
                <button class="btn btn-primary" @click="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create First Request
                </button>
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

    <!-- CREATE/EDIT REQUEST MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal request-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="isEditing ? 'Edit PBC Request' : 'Create New PBC Request'"></h3>
                <button class="modal-close" @click="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="saveRequest()">
                <div class="modal-body">
                    <!-- REQUEST INFORMATION -->
                    <div class="form-section">
                        <h4 class="form-section-title">Request Information</h4>
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
                                <label class="form-label">Category *</label>
                                <select class="form-select" x-model="requestForm.category_id" required :class="{ 'error': errors.category_id }">
                                    <option value="">Select Category</option>
                                    <template x-for="category in availableCategories" :key="category.id">
                                        <option :value="category.id" x-text="category.name + ' (' + category.code + ')'"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.category_id" x-text="errors.category_id"></div>
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

                            <div class="form-group full-width">
                                <label class="form-label">Description *</label>
                                <textarea
                                    class="form-textarea"
                                    x-model="requestForm.description"
                                    required
                                    placeholder="Describe what documents or information is needed"
                                    rows="3"
                                    :class="{ 'error': errors.description }"
                                ></textarea>
                                <div class="form-error" x-show="errors.description" x-text="errors.description"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ASSIGNMENT & TIMELINE -->
                    <div class="form-section">
                        <h4 class="form-section-title">Assignment & Timeline</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Assigned To *</label>
                                <select class="form-select" x-model="requestForm.assigned_to_id" required :class="{ 'error': errors.assigned_to_id }">
                                    <option value="">Select Assignee</option>
                                    <template x-for="user in availableUsers" :key="user.id">
                                        <option :value="user.id" x-text="user.name + ' (' + formatRole(user.role) + ')'"></option>
                                    </template>
                                </select>
                                <div class="form-error" x-show="errors.assigned_to_id" x-text="errors.assigned_to_id"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Due Date *</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    x-model="requestForm.due_date"
                                    required
                                    :class="{ 'error': errors.due_date }"
                                >
                                <div class="form-error" x-show="errors.due_date" x-text="errors.due_date"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Priority *</label>
                                <select class="form-select" x-model="requestForm.priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- NOTES -->
                    <div class="form-section">
                        <h4 class="form-section-title">Additional Notes</h4>
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
                            <label>Category:</label>
                            <span x-text="(selectedRequest?.category?.name || 'Unknown') + ' (' + (selectedRequest?.category?.code || 'N/A') + ')'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Project:</label>
                            <span x-text="selectedRequest?.project?.client?.name || 'Unknown'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Priority:</label>
                            <span x-text="formatPriority(selectedRequest?.priority)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span x-text="formatStatus(selectedRequest?.status)"></span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Assignment & Timeline</h4>
                        <div class="detail-item">
                            <label>Requested By:</label>
                            <span x-text="selectedRequest?.requestor?.name || 'Unknown'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Assigned To:</label>
                            <span x-text="selectedRequest?.assigned_to?.name || 'Unassigned'"></span>
                        </div>
                        <div class="detail-item">
                            <label>Date Requested:</label>
                            <span x-text="formatDate(selectedRequest?.date_requested)"></span>
                        </div>
                        <div class="detail-item">
                            <label>Due Date:</label>
                            <span x-text="formatDate(selectedRequest?.due_date)"></span>
                        </div>
                    </div>

                    <div class="detail-section full-width">
                        <h4>Description</h4>
                        <div class="detail-description" x-text="selectedRequest?.description"></div>
                    </div>

                    <div class="detail-section full-width" x-show="selectedRequest?.notes">
                        <h4>Notes</h4>
                        <div class="detail-notes" x-text="selectedRequest?.notes"></div>
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

    /* Common styles from project management */
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
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto;
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

    /* PBC Requests Table */
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
    .category-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 120px;
    }

    .category-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        text-align: center;
        border: 1px solid currentColor;
    }

    .category-name {
        font-size: 0.8rem;
        color: #6B7280;
        text-align: center;
    }

    .request-info {
        min-width: 250px;
    }

    .request-title {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }

    .request-description {
        font-size: 0.8rem;
        color: #6B7280;
        line-height: 1.4;
        margin-bottom: 0.5rem;
    }

    .request-project {
        font-size: 0.75rem;
        color: #9CA3AF;
        font-style: italic;
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

    .status-priority {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 100px;
    }

    .status-badge, .priority-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        text-align: center;
    }

    /* Status badges */
    .status-pending { background: #FEF3C7; color: #92400E; }
    .status-in-progress { background: #DBEAFE; color: #1E40AF; }
    .status-completed { background: #D1FAE5; color: #065F46; }
    .status-overdue { background: #FEE2E2; color: #991B1B; }
    .status-rejected { background: #FDE2E2; color: #991B1B; }

    /* Priority badges */
    .priority-low { background: #D1FAE5; color: #065F46; }
    .priority-medium { background: #FEF3C7; color: #92400E; }
    .priority-high { background: #FED7AA; color: #9A3412; }
    .priority-urgent { background: #FEE2E2; color: #991B1B; }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        min-width: 160px;
    }

    .notes-cell {
        min-width: 150px;
        max-width: 200px;
        font-size: 0.8rem;
        color: #6B7280;
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
        max-width: 1000px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .request-modal {
        max-width: 1000px;
    }

    .details-modal {
        max-width: 800px;
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

    .detail-description, .detail-notes {
        color: #1F2937;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .filters-grid {
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
        }

        .filters-grid .filter-group:nth-child(5),
        .filters-grid .filter-group:nth-child(6) {
            grid-column: span 2;
        }

        .filters-grid .filter-group:nth-child(7) {
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
            availableCategories: [],
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
                category_id: '',
                status: '',
                priority: '',
                assigned_to_id: '',
                sort_by: 'due_date',
                sort_order: 'asc',
                per_page: 25
            },
            pagination: {},
            loading: false,

            // Modal states
            showModal: false,
            showDetailsModal: false,
            showBulkAssignModal: false,
            isEditing: false,
            saving: false,

            // Form data
            requestForm: {
                project_id: '',
                category_id: '',
                title: '',
                description: '',
                assigned_to_id: '',
                due_date: '',
                priority: 'medium',
                notes: ''
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
                    console.log('📋 Loading supporting data...');

                    // Load projects
                    const projectsResponse = await fetch('/api/v1/projects?per_page=100', this.getFetchOptions());
                    if (projectsResponse.ok) {
                        const projectsResult = await projectsResponse.json();
                        this.availableProjects = projectsResult.data || [];
                        console.log('✅ Projects loaded:', this.availableProjects.length);
                    }

                    // Load categories
                    const categoriesResponse = await fetch('/api/v1/pbc-categories?per_page=100', this.getFetchOptions());
                    if (categoriesResponse.ok) {
                        const categoriesResult = await categoriesResponse.json();
                        this.availableCategories = categoriesResult.data || [];
                        console.log('✅ Categories loaded:', this.availableCategories.length);
                    } else {
                        // Fallback: create some default categories for testing
                        this.availableCategories = [
                            { id: 1, name: 'Cash and Cash Equivalents', code: 'CASH', color_code: '#10B981' },
                            { id: 2, name: 'Accounts Receivable', code: 'AR', color_code: '#3B82F6' },
                            { id: 3, name: 'Inventory', code: 'INV', color_code: '#F59E0B' },
                            { id: 4, name: 'Accounts Payable', code: 'AP', color_code: '#EF4444' },
                            { id: 5, name: 'Tax', code: 'TAX', color_code: '#EC4899' },
                            { id: 6, name: 'General Ledger', code: 'GL', color_code: '#6B7280' }
                        ];
                        console.log('⚠️ Using fallback categories');
                    }

                    // Load users
                    const usersResponse = await fetch('/api/v1/users?per_page=100', this.getFetchOptions());
                    if (usersResponse.ok) {
                        const usersResult = await usersResponse.json();
                        this.availableUsers = usersResult.data || [];
                        console.log('✅ Users loaded:', this.availableUsers.length);
                    }
                } catch (error) {
                    console.error('❌ Failed to load supporting data:', error);
                    this.showError('Failed to load supporting data: ' + error.message);
                }
            },

            // API calls
            async loadPbcRequests(page = 1) {
                console.log('🔍 Loading PBC requests - Start');
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        ...this.filters,
                        page: page
                    });

                    const response = await fetch(`/api/v1/pbc-requests?${params}`, this.getFetchOptions());

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('❌ HTTP Error:', response.status, errorText);
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();
                    console.log('📊 Response:', result);

                    if (result.success) {
                        this.pbcRequests = result.data || [];
                        this.pagination = result.pagination || {};
                        console.log('✅ PBC requests loaded:', this.pbcRequests.length);
                    } else {
                        console.error('❌ Error:', result.message);
                        this.showError('Failed to load PBC requests: ' + result.message);
                    }
                } catch (error) {
                    console.error('🚨 Network Error:', error);
                    this.showError('Failed to load PBC requests: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async loadStats() {
                try {
                    // Calculate stats from current data
                    this.stats = {
                        total: this.pbcRequests.length,
                        completed: this.pbcRequests.filter(r => r.status === 'completed').length,
                        pending: this.pbcRequests.filter(r => r.status === 'pending' || r.status === 'in_progress').length,
                        overdue: this.pbcRequests.filter(r => r.status === 'overdue' || this.isOverdue(r)).length
                    };
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
            },

            async saveRequest() {
                this.saving = true;
                this.errors = {};

                try {
                    const formData = { ...this.requestForm };

                    // Convert empty strings to null for nullable fields
                    const nullableFields = ['notes'];
                    nullableFields.forEach(field => {
                        if (formData[field] === '') {
                            formData[field] = null;
                        }
                    });

                    const url = this.isEditing
                        ? `/api/v1/pbc-requests/${formData.id}`
                        : '/api/v1/pbc-requests';

                    const method = this.isEditing ? 'PUT' : 'POST';

                    console.log('💾 Sending PBC request data:', formData);

                    const response = await fetch(url, this.getFetchOptions(method, formData));

                    const result = await response.json();
                    console.log('📤 Server response:', result);

                    if (result.success) {
                        this.showSuccess(this.isEditing ? 'PBC request updated successfully' : 'PBC request created successfully');
                        this.closeModal();
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        if (result.errors) {
                            this.errors = result.errors;
                            console.log('⚠️ Validation errors:', this.errors);
                        } else {
                            this.showError(result.message || 'Failed to save PBC request');
                        }
                    }
                } catch (error) {
                    console.error('❌ Network error:', error);
                    this.showError('Network error: ' + error.message);
                } finally {
                    this.saving = false;
                }
            },

            async deleteRequest(request) {
                if (!confirm(`Are you sure you want to delete the request "${request.title}"? This action cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch(`/api/v1/pbc-requests/${request.id}`, this.getFetchOptions('DELETE'));
                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess('PBC request deleted successfully');
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to delete PBC request');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            async completeRequest(request) {
                try {
                    const response = await fetch(`/api/v1/pbc-requests/${request.id}/complete`, this.getFetchOptions('PUT'));
                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess('PBC request marked as completed');
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to complete PBC request');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            async reopenRequest(request) {
                try {
                    const response = await fetch(`/api/v1/pbc-requests/${request.id}/reopen`, this.getFetchOptions('PUT'));
                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess('PBC request reopened');
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to reopen PBC request');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            // Modal methods
            openCreateModal() {
                this.isEditing = false;
                this.requestForm = {
                    project_id: '',
                    category_id: '',
                    title: '',
                    description: '',
                    assigned_to_id: '',
                    due_date: '',
                    priority: 'medium',
                    notes: ''
                };
                this.errors = {};
                this.showModal = true;
            },

            editRequest(request) {
                this.isEditing = true;

                // Format the due date properly for the date input
                let formattedDate = '';
                if (request.due_date) {
                    const date = new Date(request.due_date);
                    if (!isNaN(date.getTime())) {
                        formattedDate = date.toISOString().split('T')[0];
                    }
                }

                this.requestForm = {
                    id: request.id,
                    project_id: request.project_id || '',
                    category_id: request.category_id || '',
                    title: request.title || '',
                    description: request.description || '',
                    assigned_to_id: request.assigned_to_id || '',
                    due_date: formattedDate,
                    priority: request.priority || 'medium',
                    notes: request.notes || ''
                };
                this.errors = {};
                this.showModal = true;
            },

            viewRequest(request) {
                this.selectedRequest = request;
                this.showDetailsModal = true;
            },

            editRequestFromDetails() {
                this.showDetailsModal = false;
                this.editRequest(this.selectedRequest);
            },

            closeModal() {
                this.showModal = false;
                this.isEditing = false;
                this.requestForm = {};
                this.errors = {};
            },

            closeDetailsModal() {
                this.showDetailsModal = false;
                this.selectedRequest = null;
            },

            closeBulkAssignModal() {
                this.showBulkAssignModal = false;
                this.bulkAssignUserId = '';
            },

            // Selection methods
            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedRequests = this.pbcRequests.map(request => request.id);
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

            // Bulk actions
            async bulkComplete() {
                if (!confirm(`Mark ${this.selectedRequests.length} selected requests as completed?`)) return;

                try {
                    const response = await fetch('/api/v1/pbc-requests/bulk-update', this.getFetchOptions('PUT', {
                        pbc_request_ids: this.selectedRequests,
                        action: 'complete'
                    }));

                    const result = await response.json();
                    if (result.success) {
                        this.showSuccess(`${result.data.updated} requests marked as completed`);
                        this.clearSelection();
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to complete requests');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            async bulkReopen() {
                if (!confirm(`Reopen ${this.selectedRequests.length} selected requests?`)) return;

                try {
                    const response = await fetch('/api/v1/pbc-requests/bulk-update', this.getFetchOptions('PUT', {
                        pbc_request_ids: this.selectedRequests,
                        action: 'reopen'
                    }));

                    const result = await response.json();
                    if (result.success) {
                        this.showSuccess(`${result.data.updated} requests reopened`);
                        this.clearSelection();
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to reopen requests');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            bulkAssign() {
                this.showBulkAssignModal = true;
            },

            async performBulkAssign() {
                try {
                    const response = await fetch('/api/v1/pbc-requests/bulk-update', this.getFetchOptions('PUT', {
                        pbc_request_ids: this.selectedRequests,
                        action: 'assign',
                        assigned_to_id: this.bulkAssignUserId
                    }));

                    const result = await response.json();
                    if (result.success) {
                        this.showSuccess(`${result.data.updated} requests reassigned`);
                        this.closeBulkAssignModal();
                        this.clearSelection();
                        await this.loadPbcRequests();
                    } else {
                        this.showError(result.message || 'Failed to assign requests');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            async bulkDelete() {
                if (!confirm(`Delete ${this.selectedRequests.length} selected requests? This action cannot be undone.`)) return;

                try {
                    const response = await fetch('/api/v1/pbc-requests/bulk-update', this.getFetchOptions('PUT', {
                        pbc_request_ids: this.selectedRequests,
                        action: 'delete'
                    }));

                    const result = await response.json();
                    if (result.success) {
                        this.showSuccess(`${result.data.updated} requests deleted`);
                        this.clearSelection();
                        await this.loadPbcRequests();
                        await this.loadStats();
                    } else {
                        this.showError(result.message || 'Failed to delete requests');
                    }
                } catch (error) {
                    this.showError('Network error: ' + error.message);
                }
            },

            async bulkSendReminders() {
                if (!confirm(`Send reminders for ${this.selectedRequests.length} selected requests?`)) return;
                this.showSuccess(`Reminders sent for ${this.selectedRequests.length} requests`);
                this.clearSelection();
            },

            async sendReminder(request) {
                this.showSuccess(`Reminder sent for "${request.title}"`);
            },

            // Filter methods
            clearFilters() {
                this.filters = {
                    search: '',
                    project_id: '',
                    category_id: '',
                    status: '',
                    priority: '',
                    assigned_to_id: '',
                    sort_by: 'due_date',
                    sort_order: 'asc',
                    per_page: 25
                };
                this.loadPbcRequests();
            },

            async exportRequests() {
                try {
                    const params = new URLSearchParams(this.filters);
                    const response = await fetch(`/api/v1/pbc-requests/export?${params}`, this.getFetchOptions());

                    if (response.ok) {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'pbc_requests_export.xlsx';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                } catch (error) {
                    this.showError('Failed to export PBC requests');
                }
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

                for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                    pages.push(i);
                }

                return pages;
            },

            // Utility methods
            isOverdue(request) {
                if (request.status === 'completed') return false;
                const today = new Date();
                const dueDate = new Date(request.due_date);
                return dueDate < today;
            },

            getDaysUntilDue(request) {
                if (request.status === 'completed') return null;
                const today = new Date();
                const dueDate = new Date(request.due_date);
                const diffTime = dueDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays;
            },

            getDueText(request) {
                const days = this.getDaysUntilDue(request);
                if (days === null) return '';
                if (days < 0) return `${Math.abs(days)} days overdue`;
                if (days === 0) return 'Due today';
                if (days === 1) return 'Due tomorrow';
                if (days <= 3) return `Due in ${days} days`;
                return `${days} days left`;
            },

            getDueBadgeClass(request) {
                const days = this.getDaysUntilDue(request);
                if (days === null) return '';
                if (days < 0) return 'due-overdue';
                if (days <= 3) return 'due-soon';
                return 'due-ok';
            },

            formatEngagementType(type) {
                if (!type) return 'Unknown';
                return type.split('_').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
            },

            formatStatus(status) {
                if (!status) return 'Unknown';
                return status.split('_').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
            },

            formatPriority(priority) {
                if (!priority) return 'Unknown';
                return priority.charAt(0).toUpperCase() + priority.slice(1);
            },

            formatRole(role) {
                if (!role) return 'Unknown';
                return role.split('_').map(word =>
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
            },

            formatDate(dateString) {
                if (!dateString) return '';
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            },

            truncateText(text, length) {
                if (!text) return '';
                if (text.length <= length) return text;
                return text.substring(0, length) + '...';
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
