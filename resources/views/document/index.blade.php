@extends('layouts.app')

@section('title', 'Document Archive')
@section('page-title', 'Document Archive')
@section('page-subtitle', 'Manage and review accepted and rejected project files')

@section('content')
<div x-data="documentArchiveManagement()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="document-management-header">
        <div class="header-title">
            <h2>Document Archive</h2>
            <p class="header-description">Manage and review accepted and rejected project files</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="exportDocuments()" :disabled="loading">
                <i class="fas fa-download"></i>
                Export Archive
            </button>
            <button class="btn btn-primary" @click="refreshDocuments()" :disabled="loading">
                <i class="fas fa-sync-alt"></i>
                Refresh Archive
            </button>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-cards" x-show="!loading">
        <div class="summary-card total">
            <div class="card-icon">
                <i class="fas fa-archive"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.totalProjects || 3"></div>
                <div class="card-label">Total Projects</div>
            </div>
        </div>
        <div class="summary-card accepted">
            <div class="card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.acceptedFiles || 65"></div>
                <div class="card-label">Accepted Files</div>
            </div>
        </div>
        <div class="summary-card rejected">
            <div class="card-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.rejectedFiles || 12"></div>
                <div class="card-label">Rejected Files</div>
            </div>
        </div>
        <div class="summary-card storage">
            <div class="card-icon">
                <i class="fas fa-hdd"></i>
            </div>
            <div class="card-content">
                <div class="card-number" x-text="stats.totalStorage || '89.2'"></div>
                <div class="card-label">Total Storage (GB)</div>
            </div>
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Archive</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by project, client, or file name..."
                        x-model="filters.search"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Client</label>
                <select class="filter-select" x-model="filters.client">
                    <option value="">All Clients</option>
                    <option value="xyz-limited">XYZ Limited</option>
                    <option value="abc-corp">ABC Corporation</option>
                    <option value="def-industries">DEF Industries</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Template</label>
                <select class="filter-select" x-model="filters.template">
                    <option value="">All Templates</option>
                    <option value="at-700">AT-700</option>
                    <option value="standard-audit">Standard Audit</option>
                    <option value="tax-review">Tax Review</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">File Status</label>
                <select class="filter-select" x-model="filters.status">
                    <option value="">All Files</option>
                    <option value="accepted">Accepted Only</option>
                    <option value="rejected">Rejected Only</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Date Range</label>
                <select class="filter-select" x-model="filters.date_range">
                    <option value="">All Dates</option>
                    <option value="last-week">Last Week</option>
                    <option value="last-month">Last Month</option>
                    <option value="last-quarter">Last Quarter</option>
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
            <span>Loading document archive...</span>
        </div>
    </div>

    <!-- DOCUMENT ARCHIVE TABLE -->
    <div class="document-requests-card" x-show="!loading">
        <div class="table-header">
            <div class="table-title">
                <h3>Document Archive (3)</h3>
            </div>
            <div class="table-actions">
                <button class="btn btn-sm btn-secondary" @click="refreshDocuments()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="document-requests-table">
                <thead>
                    <tr>
                        <th>PBC Details</th>
                        <th>Project & Client</th>
                        <th>Template</th>
                        <th>Total Files</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(project, index) in projects" :key="project.id">
                        <tr>
                            <td>
                                <div class="request-info">
                                    <div class="request-title" x-text="project.title"></div>
                                    <div class="request-meta">
                                        <span class="meta-item">
                                            <span class="created-date" x-text="'Started ' + project.startDate"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="project-info">
                                    <div class="client-name" x-text="project.clientName"></div>
                                    <div class="project-details">
                                        <span x-text="project.projectType + ' - ' + project.period"></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="template-info">
                                    <span class="template-name" x-text="project.template"></span>
                                </div>
                            </td>
                            <td>
                                <div class="files-info">
                                    <button class="btn btn-sm btn-success files-btn accepted-btn" @click="openAcceptedFilesModal(project)">
                                        <span x-text="'Accepted Files (' + project.acceptedFiles + ')'"></span>
                                    </button>
                                    <button class="btn btn-sm btn-danger files-btn rejected-btn" @click="openRejectedFilesModal(project)">
                                        <span x-text="'Rejected Files (' + project.rejectedFiles + ')'"></span>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-xs btn-secondary" @click="openProjectFilesModal(project)" title="View All Documents">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PROJECT FILES MODAL -->
    <div class="files-modal-overlay" x-show="showProjectFilesModal" x-transition @click="closeProjectFilesModal()">
        <div class="files-modal" @click.stop>
            <!-- Modal Header -->
            <div class="files-modal-header">
                <h3 class="files-modal-title">
                    <i class="fas fa-folder-open text-blue-600"></i>
                    Project Files - <span x-text="selectedProject?.clientName || 'Project'"></span>
                </h3>
                <button class="files-modal-close" @click="closeProjectFilesModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="files-modal-body">
                <div class="files-section">
                    <div class="files-section-header">
                        <div class="section-title">
                            <i class="fas fa-folder-open section-icon project-files"></i>
                            <span class="section-text" x-text="'All Project Files (' + (allProjectFiles?.length || 0) + ')'"></span>
                        </div>
                        <button class="btn btn-sm btn-primary download-all-btn" @click="downloadAllProjectFiles()">
                            <i class="fas fa-download"></i>
                            Download All
                        </button>
                    </div>

                    <div class="files-table-container">
                        <table class="files-table">
                            <thead>
                                <tr>
                                    <th>Particulars</th>
                                    <th>File Name</th>
                                    <th>Attached By</th>
                                    <th>File Type</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(file, index) in allProjectFiles" :key="index">
                                    <tr>
                                        <td>
                                            <div class="particulars-cell">
                                                <span x-text="file.particulars"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="file-name-cell">
                                                <i class="fas fa-file-pdf file-icon pdf" x-show="file.type === 'PDF'"></i>
                                                <i class="fas fa-file-excel file-icon excel" x-show="file.type === 'XLSX'"></i>
                                                <i class="fas fa-file-word file-icon word" x-show="file.type === 'DOCX'"></i>
                                                <span x-text="file.name"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="attached-by-cell">
                                                <span x-text="file.attachedBy"></span>
                                            </div>
                                        </td>
                                        <td><span class="file-type" :class="file.type.toLowerCase()" x-text="file.type"></span></td>
                                        <td x-text="file.size"></td>
                                        <td x-text="file.date"></td>
                                        <td>
                                            <span class="status-badge" :class="file.status === 'accepted' ? 'status-accepted' : 'status-rejected'" x-text="file.status.charAt(0).toUpperCase() + file.status.slice(1)"></span>
                                        </td>
                                        <td>
                                            <div class="file-actions">
                                                <button class="btn btn-xs btn-secondary" @click="viewFile(file)" title="View File">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-xs btn-primary" @click="downloadFile(file)" title="Download File">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="files-modal-footer">
                <button class="btn btn-secondary" @click="closeProjectFilesModal()">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </button>
            </div>
        </div>
    </div>

    <!-- ACCEPTED FILES MODAL -->
    <div class="files-modal-overlay" x-show="showAcceptedModal" x-transition @click="closeAcceptedModal()">
        <div class="files-modal" @click.stop>
            <!-- Modal Header -->
            <div class="files-modal-header">
                <h3 class="files-modal-title">
                    <i class="fas fa-check-circle text-green-600"></i>
                    Accepted Files - <span x-text="selectedProject?.clientName || 'Project'"></span>
                </h3>
                <button class="files-modal-close" @click="closeAcceptedModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="files-modal-body">
                <div class="files-section">
                    <div class="files-section-header">
                        <div class="section-title">
                            <i class="fas fa-check-circle section-icon accepted-files"></i>
                            <span class="section-text" x-text="'Accepted Files (' + (selectedProject?.acceptedFiles || 0) + ')'"></span>
                        </div>
                        <button class="btn btn-sm btn-success download-all-btn" @click="downloadAllAccepted()">
                            <i class="fas fa-download"></i>
                            Download All
                        </button>
                    </div>

                    <div class="files-table-container">
                        <table class="files-table">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>File Type</th>
                                    <th>Size</th>
                                    <th>Date Accepted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(file, index) in acceptedFilesList" :key="index">
                                    <tr>
                                        <td>
                                            <div class="file-name-cell">
                                                <i class="fas fa-file-pdf file-icon pdf" x-show="file.type === 'PDF'"></i>
                                                <i class="fas fa-file-excel file-icon excel" x-show="file.type === 'XLSX'"></i>
                                                <i class="fas fa-file-word file-icon word" x-show="file.type === 'DOCX'"></i>
                                                <span x-text="file.name"></span>
                                            </div>
                                        </td>
                                        <td><span class="file-type" :class="file.type.toLowerCase()" x-text="file.type"></span></td>
                                        <td x-text="file.size"></td>
                                        <td x-text="file.dateAccepted"></td>
                                        <td>
                                            <div class="file-actions">
                                                <button class="btn btn-xs btn-secondary" @click="viewFile(file)" title="View File">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="files-modal-footer">
                <button class="btn btn-secondary" @click="closeAcceptedModal()">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </button>
            </div>
        </div>
    </div>

    <!-- REJECTED FILES MODAL -->
    <div class="files-modal-overlay" x-show="showRejectedModal" x-transition @click="closeRejectedModal()">
        <div class="files-modal" @click.stop>
            <!-- Modal Header -->
            <div class="files-modal-header">
                <h3 class="files-modal-title">
                    <i class="fas fa-times-circle text-red-600"></i>
                    Rejected Files - <span x-text="selectedProject?.clientName || 'Project'"></span>
                </h3>
                <button class="files-modal-close" @click="closeRejectedModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="files-modal-body">
                <div class="files-section">
                    <div class="files-section-header">
                        <div class="section-title">
                            <i class="fas fa-times-circle section-icon rejected-files"></i>
                            <span class="section-text" x-text="'Rejected Files (' + (selectedProject?.rejectedFiles || 0) + ')'"></span>
                        </div>
                        <button class="btn btn-sm btn-warning download-all-btn" @click="downloadAllRejected()">
                            <i class="fas fa-download"></i>
                            Download All
                        </button>
                    </div>

                    <div class="files-table-container">
                        <table class="files-table">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>File Type</th>
                                    <th>Size</th>
                                    <th>Date Rejected</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(file, index) in rejectedFilesList" :key="index">
                                    <tr>
                                        <td>
                                            <div class="file-name-cell">
                                                <i class="fas fa-file-pdf file-icon pdf" x-show="file.type === 'PDF'"></i>
                                                <i class="fas fa-file-excel file-icon excel" x-show="file.type === 'XLSX'"></i>
                                                <i class="fas fa-file-word file-icon word" x-show="file.type === 'DOCX'"></i>
                                                <span x-text="file.name"></span>
                                            </div>
                                        </td>
                                        <td><span class="file-type" :class="file.type.toLowerCase()" x-text="file.type"></span></td>
                                        <td x-text="file.size"></td>
                                        <td x-text="file.dateRejected"></td>
                                        <td>
                                            <div class="file-actions">
                                                <button class="btn btn-xs btn-secondary" @click="viewFile(file)" title="View File">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="files-modal-footer">
                <button class="btn btn-secondary" @click="closeRejectedModal()">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Document Archive Management Styles */
    .document-management-header {
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

    .summary-card.total::before { background: #8B5CF6; }
    .summary-card.accepted::before { background: #10B981; }
    .summary-card.rejected::before { background: #EF4444; }
    .summary-card.storage::before { background: #3B82F6; }

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

    .summary-card.total .card-icon { background: #8B5CF6; }
    .summary-card.accepted .card-icon { background: #10B981; }
    .summary-card.rejected .card-icon { background: #EF4444; }
    .summary-card.storage .card-icon { background: #3B82F6; }

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

    .btn-success:hover:not(:disabled) {
        background: #059669;
    }

    .btn-warning {
        background: #F59E0B;
        color: white;
    }

    .btn-warning:hover:not(:disabled) {
        background: #D97706;
    }

    .btn-danger {
        background: #EF4444;
        color: white;
    }

    .btn-danger:hover:not(:disabled) {
        background: #DC2626;
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

    .accepted-btn {
        background: #10B981;
        color: white;
        border: 1px solid #059669;
    }

    .accepted-btn:hover {
        background: #059669;
    }

    .rejected-btn {
        background: #EF4444;
        color: white;
        border: 1px solid #DC2626;
    }

    .rejected-btn:hover {
        background: #DC2626;
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

    .document-requests-card {
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

    .document-requests-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .document-requests-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
        white-space: nowrap;
    }

    .document-requests-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: top;
    }

    .document-requests-table tbody tr:hover {
        background: #F9FAFB;
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

    .files-info {
        min-width: 180px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
        min-width: 80px;
    }

    /* Files Modal Styles */
    .files-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .files-modal {
        background: white;
        border-radius: 16px;
        max-width: 1400px;
        width: 100%;
        max-height: 90vh;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .files-modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #F9FAFB;
        flex-shrink: 0;
    }

    .files-modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .files-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9CA3AF;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .files-modal-close:hover {
        background: #F3F4F6;
        color: #6B7280;
    }

    .files-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        min-height: 0;
    }

    .files-section {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .files-section-header {
        padding: 1rem 1.5rem;
        background: #F9FAFB;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .section-icon.accepted-files {
        background: #D1FAE5;
        color: #065F46;
    }

    .section-icon.rejected-files {
        background: #FEE2E2;
        color: #991B1B;
    }

    .section-icon.project-files {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .section-text {
        font-weight: 600;
        color: #374151;
        font-size: 1rem;
    }

    .download-all-btn {
        background: #10B981;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .download-all-btn:hover {
        background: #059669;
    }

    .files-table-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 400px;
        border-top: 1px solid #E5E7EB;
    }

    .files-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
        font-size: 0.8rem;
    }

    .files-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.75rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .files-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: middle;
        font-size: 0.75rem;
        white-space: nowrap;
    }

    .files-table tbody tr:hover {
        background: #F9FAFB;
    }

    .files-table tbody tr:last-child td {
        border-bottom: none;
    }

    .particulars-cell {
        max-width: 200px;
        min-width: 150px;
    }

    .particulars-cell span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.3;
    }

    .file-name-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 180px;
        max-width: 250px;
    }

    .file-name-cell span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .attached-by-cell {
        min-width: 120px;
    }

    .file-icon {
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 0.7rem;
        flex-shrink: 0;
    }

    .file-icon.pdf {
        background: #FEE2E2;
        color: #DC2626;
    }

    .file-icon.excel {
        background: #D1FAE5;
        color: #059669;
    }

    .file-icon.word {
        background: #DBEAFE;
        color: #2563EB;
    }

    .file-type {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .file-type.pdf {
        background: #FEE2E2;
        color: #DC2626;
    }

    .file-type.xlsx {
        background: #D1FAE5;
        color: #059669;
    }

    .file-type.docx {
        background: #DBEAFE;
        color: #2563EB;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.status-accepted {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-badge.status-rejected {
        background: #FEE2E2;
        color: #991B1B;
    }

    .file-actions {
        display: flex;
        gap: 0.25rem;
        align-items: center;
        justify-content: center;
        min-width: 80px;
    }

    .file-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
        min-width: 28px;
        height: 28px;
        border-radius: 4px;
    }

    .files-modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #E5E7EB;
        background: #F9FAFB;
        display: flex;
        justify-content: flex-start;
        flex-shrink: 0;
    }

    .text-green-600 {
        color: #059669;
    }

    .text-red-600 {
        color: #DC2626;
    }

    .text-blue-600 {
        color: #2563EB;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .document-management-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions {
            justify-content: stretch;
        }

        .files-modal {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
        }

        .files-table {
            min-width: 800px;
        }
    }

    @media (max-width: 480px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }

        .files-table {
            min-width: 600px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function documentArchiveManagement() {
        return {
            // Data
            projects: [
                {
                    id: 1,
                    title: 'AT-700 Annual Audit 2024 - XYZ Limited',
                    clientName: 'XYZ Limited',
                    projectType: 'Tax',
                    period: '2024-12-31',
                    template: 'AT-700',
                    acceptedFiles: 28,
                    rejectedFiles: 6,
                    startDate: 'Jul 5, 2025',
                    status: 'active'
                },
                {
                    id: 2,
                    title: 'Standard Audit 2024 - ABC Corporation',
                    clientName: 'ABC Corporation',
                    projectType: 'Audit',
                    period: '2024-12-31',
                    template: 'Standard Audit',
                    acceptedFiles: 15,
                    rejectedFiles: 3,
                    startDate: 'Jun 20, 2025',
                    status: 'active'
                },
                {
                    id: 3,
                    title: 'Tax Review 2024 - DEF Industries',
                    clientName: 'DEF Industries',
                    projectType: 'Tax Review',
                    period: '2024-12-31',
                    template: 'Tax Review',
                    acceptedFiles: 22,
                    rejectedFiles: 3,
                    startDate: 'May 15, 2025',
                    status: 'completed'
                }
            ],
            selectedProject: null,
            acceptedFilesList: [],
            rejectedFilesList: [],
            allProjectFiles: [],
            stats: {
                totalProjects: 3,
                acceptedFiles: 65,
                rejectedFiles: 12,
                totalStorage: '89.2'
            },
            filters: {
                search: '',
                client: '',
                template: '',
                status: '',
                date_range: ''
            },
            loading: false,

            // Modal states
            showAcceptedModal: false,
            showRejectedModal: false,
            showProjectFilesModal: false,

            // Initialize
            init() {
                console.log('🚀 Document Archive Management Init (UI Only)');
                this.loading = false;
            },

            // Modal management
            openAcceptedFilesModal(project) {
                this.selectedProject = project;
                this.acceptedFilesList = this.generateAcceptedFiles(project);
                this.showAcceptedModal = true;
                console.log('Opening accepted files modal for:', project.clientName);
            },

            openRejectedFilesModal(project) {
                this.selectedProject = project;
                this.rejectedFilesList = this.generateRejectedFiles(project);
                this.showRejectedModal = true;
                console.log('Opening rejected files modal for:', project.clientName);
            },

            openProjectFilesModal(project) {
                this.selectedProject = project;
                this.allProjectFiles = this.generateAllProjectFiles(project);
                this.showProjectFilesModal = true;
                console.log('Opening project files modal for:', project.clientName);
            },

            closeAcceptedModal() {
                this.showAcceptedModal = false;
                this.selectedProject = null;
                this.acceptedFilesList = [];
            },

            closeRejectedModal() {
                this.showRejectedModal = false;
                this.selectedProject = null;
                this.rejectedFilesList = [];
            },

            closeProjectFilesModal() {
                this.showProjectFilesModal = false;
                this.selectedProject = null;
                this.allProjectFiles = [];
            },

            // Generate all project files data
            generateAllProjectFiles(project) {
                const allFiles = [
                    {
                        particulars: 'Articles of Incorporation and Bylaws',
                        name: 'Articles_of_Incorporation.pdf',
                        attachedBy: 'John Martinez',
                        type: 'PDF',
                        size: '2.4 MB',
                        date: 'Jul 8, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'BIR Certificate of Registration',
                        name: 'BIR_Certificate_2024.pdf',
                        attachedBy: 'Sarah Wilson',
                        type: 'PDF',
                        size: '856 KB',
                        date: 'Jul 7, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Financial Statements Q4 2024',
                        name: 'Financial_Statements_Q4.xlsx',
                        attachedBy: 'Carlos Reyes',
                        type: 'XLSX',
                        size: '3.2 MB',
                        date: 'Jul 6, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Board Meeting Minutes',
                        name: 'Board_Minutes_December.docx',
                        attachedBy: 'Maria Garcia',
                        type: 'DOCX',
                        size: '124 KB',
                        date: 'Jul 5, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Bank Statements 2024',
                        name: 'Bank_Statements_2024.pdf',
                        attachedBy: 'Anna Thompson',
                        type: 'PDF',
                        size: '4.1 MB',
                        date: 'Jul 4, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Trial Balance Year End',
                        name: 'Trial_Balance_2024.xlsx',
                        attachedBy: 'David Wilson',
                        type: 'XLSX',
                        size: '1.8 MB',
                        date: 'Jul 3, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'General Ledger Complete',
                        name: 'General_Ledger_2024.xlsx',
                        attachedBy: 'Michelle Lopez',
                        type: 'XLSX',
                        size: '8.7 MB',
                        date: 'Jul 2, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Incomplete Financial Report',
                        name: 'Incomplete_Financial_Report.pdf',
                        attachedBy: 'Kevin Taylor',
                        type: 'PDF',
                        size: '1.2 MB',
                        date: 'Jul 9, 2025',
                        status: 'rejected'
                    },
                    {
                        particulars: 'Bank Statement - Unclear Quality',
                        name: 'Unclear_Bank_Statement.pdf',
                        attachedBy: 'Amanda Clark',
                        type: 'PDF',
                        size: '2.1 MB',
                        date: 'Jul 8, 2025',
                        status: 'rejected'
                    },
                    {
                        particulars: 'Contract Document - Missing Pages',
                        name: 'Missing_Pages_Document.docx',
                        attachedBy: 'Ryan Hall',
                        type: 'DOCX',
                        size: '89 KB',
                        date: 'Jul 7, 2025',
                        status: 'rejected'
                    },
                    {
                        particulars: 'Tax Returns 2023',
                        name: 'Tax_Returns_2023.pdf',
                        attachedBy: 'Natalie White',
                        type: 'PDF',
                        size: '1.9 MB',
                        date: 'Jun 30, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Payroll Summary Annual',
                        name: 'Payroll_Summary_2024.xlsx',
                        attachedBy: 'James Rodriguez',
                        type: 'XLSX',
                        size: '945 KB',
                        date: 'Jun 29, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Fixed Assets Register',
                        name: 'Fixed_Assets_Register.pdf',
                        attachedBy: 'Emily Davis',
                        type: 'PDF',
                        size: '1.2 MB',
                        date: 'Jun 28, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Insurance Policy Documents',
                        name: 'Insurance_Policies.pdf',
                        attachedBy: 'Michael Brown',
                        type: 'PDF',
                        size: '890 KB',
                        date: 'Jun 26, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Legal Documents and Contracts',
                        name: 'Legal_Documents.docx',
                        attachedBy: 'Jennifer Lee',
                        type: 'DOCX',
                        size: '234 KB',
                        date: 'Jun 25, 2025',
                        status: 'accepted'
                    },
                    {
                        particulars: 'Outdated Certificate Copy',
                        name: 'Outdated_Certificate.pdf',
                        attachedBy: 'Lisa Chen',
                        type: 'PDF',
                        size: '567 KB',
                        date: 'Jun 24, 2025',
                        status: 'rejected'
                    }
                ];

                return allFiles;
            },

            // Generate fake accepted files data
            generateAcceptedFiles(project) {
                const acceptedFiles = [
                    { name: 'Articles_of_Incorporation.pdf', type: 'PDF', size: '2.4 MB', dateAccepted: 'Jul 8, 2025' },
                    { name: 'BIR_Certificate_2024.pdf', type: 'PDF', size: '856 KB', dateAccepted: 'Jul 7, 2025' },
                    { name: 'Financial_Statements_Q4.xlsx', type: 'XLSX', size: '3.2 MB', dateAccepted: 'Jul 6, 2025' },
                    { name: 'Board_Minutes_December.docx', type: 'DOCX', size: '124 KB', dateAccepted: 'Jul 5, 2025' },
                    { name: 'Bank_Statements_2024.pdf', type: 'PDF', size: '4.1 MB', dateAccepted: 'Jul 4, 2025' },
                    { name: 'Trial_Balance_2024.xlsx', type: 'XLSX', size: '1.8 MB', dateAccepted: 'Jul 3, 2025' },
                    { name: 'General_Ledger_2024.xlsx', type: 'XLSX', size: '8.7 MB', dateAccepted: 'Jul 2, 2025' },
                    { name: 'Audit_Adjustments.docx', type: 'DOCX', size: '356 KB', dateAccepted: 'Jul 1, 2025' },
                    { name: 'Tax_Returns_2023.pdf', type: 'PDF', size: '1.9 MB', dateAccepted: 'Jun 30, 2025' },
                    { name: 'Payroll_Summary_2024.xlsx', type: 'XLSX', size: '945 KB', dateAccepted: 'Jun 29, 2025' },
                    { name: 'Fixed_Assets_Register.pdf', type: 'PDF', size: '1.2 MB', dateAccepted: 'Jun 28, 2025' },
                    { name: 'Inventory_Report_2024.xlsx', type: 'XLSX', size: '2.1 MB', dateAccepted: 'Jun 27, 2025' },
                    { name: 'Insurance_Policies.pdf', type: 'PDF', size: '890 KB', dateAccepted: 'Jun 26, 2025' },
                    { name: 'Legal_Documents.docx', type: 'DOCX', size: '234 KB', dateAccepted: 'Jun 25, 2025' },
                    { name: 'Contract_Agreements.pdf', type: 'PDF', size: '1.5 MB', dateAccepted: 'Jun 24, 2025' }
                ];

                return acceptedFiles.slice(0, project.acceptedFiles);
            },

            // Generate fake rejected files data
            generateRejectedFiles(project) {
                const rejectedFiles = [
                    { name: 'Incomplete_Financial_Report.pdf', type: 'PDF', size: '1.2 MB', dateRejected: 'Jul 9, 2025' },
                    { name: 'Unclear_Bank_Statement.pdf', type: 'PDF', size: '2.1 MB', dateRejected: 'Jul 8, 2025' },
                    { name: 'Missing_Pages_Document.docx', type: 'DOCX', size: '89 KB', dateRejected: 'Jul 7, 2025' },
                    { name: 'Outdated_Certificate.pdf', type: 'PDF', size: '567 KB', dateRejected: 'Jul 6, 2025' },
                    { name: 'Wrong_Format_Ledger.xlsx', type: 'XLSX', size: '734 KB', dateRejected: 'Jul 5, 2025' },
                    { name: 'Illegible_Signature_Doc.pdf', type: 'PDF', size: '445 KB', dateRejected: 'Jul 4, 2025' }
                ];

                return rejectedFiles.slice(0, project.rejectedFiles);
            },

            // Utility functions
            exportDocuments() {
                console.log('Exporting document archive...');
                this.showAlert('Exporting document archive', 'info');
            },

            refreshDocuments() {
                console.log('Refreshing document archive...');
                this.loading = true;
                setTimeout(() => {
                    this.loading = false;
                    this.showAlert('Document archive refreshed!', 'success');
                }, 1000);
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    client: '',
                    template: '',
                    status: '',
                    date_range: ''
                };
                console.log('Filters cleared');
            },

            // File actions
            viewFile(file) {
                console.log('Viewing file:', file.name);
                this.showAlert(`Opening ${file.name} for preview`, 'info');
            },

            downloadFile(file) {
                console.log('Downloading file:', file.name);
                this.showAlert(`Downloading ${file.name}`, 'success');
            },

            downloadAllAccepted() {
                console.log('Downloading all accepted files for:', this.selectedProject?.clientName);
                this.showAlert(`Downloading all accepted files for ${this.selectedProject?.clientName}`, 'success');
            },

            downloadAllRejected() {
                console.log('Downloading all rejected files for:', this.selectedProject?.clientName);
                this.showAlert(`Downloading all rejected files for ${this.selectedProject?.clientName}`, 'warning');
            },

            downloadAllProjectFiles() {
                console.log('Downloading all project files for:', this.selectedProject?.clientName);
                this.showAlert(`Downloading all project files for ${this.selectedProject?.clientName}`, 'success');
            },

            // Alert system
            showAlert(message, type = 'info') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <div class="alert-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;

                document.body.appendChild(alert);
                setTimeout(() => alert.classList.add('show'), 100);

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
