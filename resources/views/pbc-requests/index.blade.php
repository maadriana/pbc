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
                Export Reports
            </button>
            <button class="btn btn-primary" @click="openCreateModal()" :disabled="loading">
                <i class="fas fa-plus"></i>
                Add Request
            </button>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-cards" x-show="!loading">
        <div class="summary-card total">
            <div class="card-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.total || 1"></div>
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
                <div class="card-number" x-text="stats.pending || 1"></div>
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
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Project</label>
                <select class="filter-select" x-model="filters.project_id">
                    <option value="">All Projects</option>
                    <option value="1">XYZ Limited - Tax 2024-12-31</option>
                    <option value="2">ABC Corp - Audit 2024</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Template</label>
                <select class="filter-select" x-model="filters.template_id">
                    <option value="">All Templates</option>
                    <option value="1">AT-700</option>
                    <option value="2">Standard Audit</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.status">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Assigned To</label>
                <select class="filter-select" x-model="filters.assigned_to">
                    <option value="">All Assignees</option>
                    <option value="1">Carlos Reyes (Guest)</option>
                    <option value="2">John Doe (Admin)</option>
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
                <h3>PBC Requests (1)</h3>
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
                        <th>PBC Details</th>
                        <th>Project&Client</th>
                        <th>Template</th>
                        <th>Progress</th>
                        <th>Received Files (3)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="request-info">
                                <div class="request-title">AT-700 Annual Audit 2024 - XYZ Limited</div>
                                <div class="request-meta">
                                    <span class="meta-item">
                                        <span class="created-date">Created Jul 5, 2025</span>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="project-info">
                                <div class="client-name">XYZ Limited</div>
                                <div class="project-details">
                                    <span>Tax - 2024-12-31</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="template-info">
                                <span class="template-name">AT-700</span>
                            </div>
                        </td>
                        <td>
                            <div class="progress-info">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 23%"></div>
                                </div>
                                <div class="progress-text">
                                    23% (9/39)
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="files-info">
                                <button class="btn btn-sm btn-primary files-btn mark-received-btn" @click="markAsReceived()">
                                    <i class="fas fa-check"></i>
                                    Mark as Received
                                </button>
                                <button class="btn btn-sm btn-secondary files-btn view-files-btn" @click="openFilesModal()">
                                    <i class="fas fa-folder-open"></i>
                                    View Files
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn btn-xs btn-secondary" @click="viewRequest()" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-xs btn-warning" @click="editRequest()" title="Edit Request">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-xs btn-danger" @click="deleteRequest()" title="Delete Request">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CREATE MODAL -->
    <div class="modal-overlay" x-show="showModal" x-transition @click="closeModal()">
        <div class="modal custom-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Create PBC Request</h3>
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
                                <select class="form-select" x-model="requestForm.project_id" required>
                                    <option value="">Select Project</option>
                                    <option value="1">XYZ Limited - Tax 2024-12-31</option>
                                    <option value="2">ABC Corp - Audit 2024</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Title *</label>
                                <input
                                    type="text"
                                    class="form-input"
                                    x-model="requestForm.title"
                                    required
                                    placeholder="Enter request title"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Template</h4>
                        <div class="template-selection">
                            <div class="template-option">
                                <a href="{{ route('pbc-templates.create') }}" class="template-badge selected">
                                    AT - 700
                                </a>
                            </div>
                            <div class="template-option">
                                <a href="{{ route('pbc-templates.create') }}" class="template-badge custom">
                                    <i class="fas fa-plus"></i>
                                    CUSTOM
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 class="form-section-title">Assignment & Timeline</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Assigned to</label>
                                <select class="form-select" x-model="requestForm.assigned_to">
                                    <option value="">Select Assignee</option>
                                    <option value="1">Carlos Reyes (Guest)</option>
                                    <option value="2">John Doe (Admin)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Due Date</label>
                                <input
                                    type="date"
                                    class="form-input"
                                    x-model="requestForm.due_date"
                                    placeholder="dd/mm/yyyy"
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

    <!-- FILES MODAL -->
    @include('pbc-requests.files-modal')
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

    .btn-xs {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }

    /* Files buttons */
    .files-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        white-space: nowrap;
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
    }

    .mark-received-btn {
        background: #3B82F6;
        color: white;
        border: 1px solid #2563EB;
    }

    .mark-received-btn:hover {
        background: #2563EB;
    }

    .view-files-btn {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #D1D5DB;
    }

    .view-files-btn:hover {
        background: #E5E7EB;
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

    .created-date {
        font-size: 0.75rem;
        color: #6B7280;
    }

    .project-info {
        min-width: 150px;
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

    .template-info {
        min-width: 100px;
    }

    .template-name {
        font-weight: 500;
        color: #1F2937;
        display: block;
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

    .due-date-info {
        min-width: 100px;
    }

    .date-text {
        font-size: 0.8rem;
        color: #6B7280;
    }

    .files-info {
        min-width: 150px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        min-width: 120px;
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
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
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

    .template-selection {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .template-option {
        cursor: pointer;
    }

    .template-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        position: relative;
        text-decoration: none;
    }

    .template-badge.selected {
        background: #10B981;
        color: white;
    }

    .template-badge.custom {
        background: #6B7280;
        color: white;
    }

    .template-badge:hover {
        transform: translateY(-1px);
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
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
    }

    @media (max-width: 480px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }
    }

    /* Alpine.js Transitions */
    [x-cloak] { display: none !important; }

    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.3s ease;
    }

    .fade-enter-from, .fade-leave-to {
        opacity: 0;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function pbcRequestManagement() {
        return {
            // Data
            pbcRequests: [
                {
                    id: 1,
                    title: 'AT-700 Annual Audit 2024 - XYZ Limited',
                    client_name: 'XYZ Limited',
                    project: { engagement_type: 'tax' },
                    template: { name: 'AT-700' },
                    completion_percentage: 23,
                    completed_items: 9,
                    total_items: 39,
                    assignedTo: { name: 'Carlos Reyes', role: 'guest' },
                    due_date: '2025-08-04',
                    status: 'active',
                    created_at: '2025-07-05'
                }
            ],
            selectedRequests: [],
            stats: {
                total: 1,
                completed: 0,
                pending: 1,
                overdue: 0
            },
            filters: {
                search: '',
                project_id: '',
                template_id: '',
                status: '',
                assigned_to: ''
            },
            loading: false,

            // Modal states
            showModal: false,
            showTemplateModal: false,
            showFilesModal: false,
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

            selectedTemplate: 'at700',

            // Initialize
            init() {
                console.log('🚀 PBC Request Management Init (UI Only)');
                this.loading = false;

                // Check if we should open the create modal
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('openModal') === 'create') {
                    this.openCreateModal();
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
                this.selectedTemplate = 'at700';
                this.showModal = true;
            },

            openCreateFromTemplateModal() {
                // For demo purposes, just open the regular modal
                this.openCreateModal();
            },

            closeModal() {
                this.showModal = false;
                this.showTemplateModal = false;
                this.showFilesModal = false;
                this.isEditing = false;
                this.saving = false;
            },

            // Files modal management
            openFilesModal() {
                this.showFilesModal = true;
                console.log('Opening files modal');
            },

            markAsReceived() {
                console.log('Marking files as received');
                this.showAlert('Files marked as received!', 'success');
            },

            // Template selection
            selectTemplate(templateType) {
                this.selectedTemplate = templateType;
                if (templateType === 'at700') {
                    this.requestForm.template_id = '1';
                } else {
                    this.requestForm.template_id = '';
                }
            },

            // Form submission
            saveRequest() {
                this.saving = true;

                // Simulate API call
                setTimeout(() => {
                    console.log('Request saved:', this.requestForm);
                    this.showAlert('PBC request created successfully!', 'success');
                    this.closeModal();
                    this.saving = false;
                }, 1500);
            },

            // Utility functions
            viewRequest(request = null) {
                console.log('Viewing request:', request || this.pbcRequests[0]);
                // Navigate to PBC template view
                window.location.href = '/pbc-templates';
            },

            editRequest(request = null) {
                console.log('Editing request:', request || this.pbcRequests[0]);

                window.location.href = '/pbc-templates/edit';
            },

            deleteRequest(request = null) {
                if (confirm('Delete this request? This action cannot be undone.')) {
                    console.log('Deleting request:', request || this.pbcRequests[0]);
                    this.showAlert('Request deleted successfully!', 'success');
                }
            },

            exportRequests() {
                console.log('Exporting requests...');
                this.showAlert('Export functionality would download Excel file', 'info');
            },

            loadPbcRequests() {
                console.log('Refreshing requests...');
                this.showAlert('Requests refreshed!', 'success');
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    project_id: '',
                    template_id: '',
                    status: '',
                    assigned_to: ''
                };
                console.log('Filters cleared');
            },

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
