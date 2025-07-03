@extends('layouts.app')

@section('title', 'Upload Center')
@section('page-title', 'Upload Center')
@section('page-subtitle', 'Upload, manage, and track document submissions')

@section('content')
<div x-data="uploadCenter()" x-init="init()">
    <!-- HEADER ACTIONS -->
    <div class="upload-center-header">
        <div class="header-title">
            <h2>Document Upload Center</h2>
            <p class="header-description">Upload documents, track submissions, and manage file approvals</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" @click="refreshDocuments()">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
            @if(auth()->user()->hasPermission('upload_document'))
            <button class="btn btn-primary" @click="openUploadModal()">
                <i class="fas fa-cloud-upload-alt"></i>
                Upload Files
            </button>
            @endif
        </div>
    </div>

    <!-- STORAGE STATS -->
    <div class="storage-stats" x-show="!loading">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-files"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" x-text="stats.total_documents || 0"></div>
                <div class="stat-label">Total Files</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" x-text="stats.pending_documents || 0"></div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" x-text="stats.approved_documents || 0"></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon storage">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" x-text="stats.total_size_formatted || '0 B'"></div>
                <div class="stat-label">Storage Used</div>
            </div>
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Search Files</label>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        type="text"
                        class="filter-input search-input"
                        placeholder="Search by filename, PBC request..."
                        x-model="filters.search"
                        @input.debounce.500ms="loadDocuments()"
                    >
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" x-model="filters.status" @change="loadDocuments()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">File Type</label>
                <select class="filter-select" x-model="filters.file_type" @change="loadDocuments()">
                    <option value="">All Types</option>
                    <option value="pdf">PDF</option>
                    <option value="doc">DOC</option>
                    <option value="docx">DOCX</option>
                    <option value="xls">XLS</option>
                    <option value="xlsx">XLSX</option>
                    <option value="ppt">PPT</option>
                    <option value="pptx">PPTX</option>
                    <option value="jpg">JPG</option>
                    <option value="png">PNG</option>
                    <option value="txt">TXT</option>
                    <option value="csv">CSV</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">PBC Request</label>
                <select class="filter-select" x-model="filters.pbc_request_id" @change="loadDocuments()">
                    <option value="">All Requests</option>
                    <template x-for="request in availablePbcRequests" :key="request.id">
                        <option :value="request.id" x-text="request.title"></option>
                    </template>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Uploaded By</label>
                <select class="filter-select" x-model="filters.uploaded_by" @change="loadDocuments()">
                    <option value="">All Users</option>
                    <template x-for="user in availableUsers" :key="user.id">
                        <option :value="user.id" x-text="user.name"></option>
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
            <span>Loading documents...</span>
        </div>
    </div>

    <!-- DOCUMENTS GRID -->
    <div class="documents-section" x-show="!loading">
        <div class="section-header">
            <h3>Documents (<span x-text="pagination.total || 0"></span>)</h3>
            <div class="view-toggle">
                <button class="view-btn" :class="{'active': viewMode === 'grid'}" @click="viewMode = 'grid'">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" :class="{'active': viewMode === 'list'}" @click="viewMode = 'list'">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>

        <!-- GRID VIEW -->
        <div x-show="viewMode === 'grid'" class="documents-grid">
            <template x-for="document in documents" :key="document.id">
                <div class="document-card" :class="{ 'selected': selectedDocuments.includes(document.id) }">
                    <div class="document-header">
                        <input type="checkbox"
                               :checked="selectedDocuments.includes(document.id)"
                               @change="toggleDocumentSelection(document.id)">
                        <div class="file-icon" :class="getFileIconClass(document.file_type)">
                            <i :class="getFileIcon(document.file_type)"></i>
                        </div>
                        <div class="document-actions">
                            <div class="dropdown">
                                <button class="action-btn" @click="toggleDropdown(document.id)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" :class="{ 'active': activeDropdown === document.id }">
                                    <a href="#" @click.prevent="viewDocument(document)">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" @click.prevent="downloadDocument(document)">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    @if(auth()->user()->hasPermission('approve_document'))
                                    <template x-if="document.status === 'pending'">
                                        <div>
                                            <a href="#" @click.prevent="approveDocument(document)">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                            <a href="#" @click.prevent="rejectDocument(document)">
                                                <i class="fas fa-times"></i> Reject
                                            </a>
                                        </div>
                                    </template>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete_document'))
                                    <a href="#" @click.prevent="deleteDocument(document)" class="text-red-600">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="document-preview" @click="viewDocument(document)">
                        <div class="file-thumbnail">
                            <template x-if="isImageFile(document.file_type)">
                                <img :src="getDocumentUrl(document)" :alt="document.original_name" class="thumbnail-image">
                            </template>
                            <template x-if="!isImageFile(document.file_type)">
                                <div class="file-type-badge" x-text="document.file_type.toUpperCase()"></div>
                            </template>
                        </div>
                    </div>

                    <div class="document-info">
                        <h4 class="document-title" :title="document.original_name" x-text="truncateText(document.original_name, 30)"></h4>
                        <p class="document-size" x-text="formatFileSize(document.file_size)"></p>
                        <p class="document-date" x-text="formatDate(document.created_at)"></p>
                        <div class="document-status">
                            <span class="status-badge" :class="`status-${document.status}`" x-text="document.status.toUpperCase()"></span>
                        </div>
                        <p class="document-uploader">by <span x-text="document.uploaded_by?.name || 'Unknown'"></span></p>
                        <p class="document-request" x-text="'PBC: ' + (document.pbc_request?.title || 'N/A')"></p>
                    </div>
                </div>
            </template>
        </div>

        <!-- LIST VIEW -->
        <div x-show="viewMode === 'list'" class="documents-table-container">
            <table class="documents-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" @change="toggleSelectAll($event)"></th>
                        <th>File</th>
                        <th>PBC Request</th>
                        <th>Uploaded By</th>
                        <th>Upload Date</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="document in documents" :key="document.id">
                        <tr :class="{ 'selected': selectedDocuments.includes(document.id) }">
                            <td>
                                <input type="checkbox"
                                       :checked="selectedDocuments.includes(document.id)"
                                       @change="toggleDocumentSelection(document.id)">
                            </td>
                            <td>
                                <div class="file-info">
                                    <div class="file-icon-small" :class="getFileIconClass(document.file_type)">
                                        <i :class="getFileIcon(document.file_type)"></i>
                                    </div>
                                    <div class="file-details">
                                        <div class="file-name" x-text="document.original_name"></div>
                                        <div class="file-type" x-text="document.file_type.toUpperCase()"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="pbc-info">
                                    <div class="pbc-title" x-text="document.pbc_request?.title || 'N/A'"></div>
                                    <div class="pbc-category" x-text="document.pbc_request?.category?.name || ''"></div>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-name" x-text="document.uploaded_by?.name || 'Unknown'"></div>
                                    <div class="user-role" x-text="document.uploaded_by?.role || ''"></div>
                                </div>
                            </td>
                            <td class="date-cell" x-text="formatDate(document.created_at)"></td>
                            <td class="size-cell" x-text="formatFileSize(document.file_size)"></td>
                            <td>
                                <span class="status-badge" :class="`status-${document.status}`" x-text="document.status.toUpperCase()"></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-xs btn-secondary" @click="viewDocument(document)" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-xs btn-primary" @click="downloadDocument(document)" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    @if(auth()->user()->hasPermission('approve_document'))
                                    <template x-if="document.status === 'pending'">
                                        <div class="approval-buttons">
                                            <button class="btn btn-xs btn-success" @click="approveDocument(document)" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-xs btn-warning" @click="rejectDocument(document)" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete_document'))
                                    <button class="btn btn-xs btn-danger" @click="deleteDocument(document)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- EMPTY STATE -->
        <div x-show="documents.length === 0 && !loading" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h3>No documents found</h3>
            <p>Upload your first document or adjust your search filters.</p>
            @if(auth()->user()->hasPermission('upload_document'))
            <button class="btn btn-primary" @click="openUploadModal()">
                <i class="fas fa-cloud-upload-alt"></i>
                Upload Documents
            </button>
            @endif
        </div>

        <!-- PAGINATION -->
        <div class="table-pagination" x-show="documents.length > 0">
            <div class="pagination-info">
                Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                of <span x-text="pagination.total || 0"></span> documents
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <template x-for="page in visiblePages" :key="page">
                    <button class="pagination-btn" :class="{ 'active': page === pagination.current_page }" @click="changePage(page)" x-text="page"></button>
                </template>
                <button class="pagination-btn" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- BULK ACTIONS -->
    <div class="bulk-actions" x-show="selectedDocuments.length > 0" x-transition>
        <div class="bulk-actions-content">
            <span class="selected-count"><span x-text="selectedDocuments.length"></span> documents selected</span>
            <div class="bulk-actions-buttons">
                @if(auth()->user()->hasPermission('approve_document'))
                <button class="btn btn-sm btn-success" @click="bulkApprove()">
                    <i class="fas fa-check"></i>
                    Approve
                </button>
                <button class="btn btn-sm btn-warning" @click="bulkReject()">
                    <i class="fas fa-times"></i>
                    Reject
                </button>
                @endif
                <button class="btn btn-sm btn-primary" @click="bulkDownload()">
                    <i class="fas fa-download"></i>
                    Download
                </button>
                @if(auth()->user()->hasPermission('delete_document'))
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

    <!-- UPLOAD MODAL -->
    <div class="modal-overlay" x-show="showUploadModal" x-transition @click="closeUploadModal()">
        <div class="modal upload-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Upload Documents</h3>
                <button class="modal-close" @click="closeUploadModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="uploadFiles()">
                <div class="modal-body">
                    <!-- PBC Request Selection -->
                    <div class="form-group">
                        <label class="form-label">PBC Request *</label>
                        <select class="form-select" x-model="uploadForm.pbc_request_id" required>
                            <option value="">Select PBC Request</option>
                            <template x-for="request in availablePbcRequests" :key="request.id">
                                <option :value="request.id" x-text="request.title + ' - ' + (request.project?.client?.name || 'Unknown Client')"></option>
                            </template>
                        </select>
                        <div class="form-error" x-show="uploadErrors.pbc_request_id" x-text="uploadErrors.pbc_request_id"></div>
                    </div>

                    <!-- File Upload Area -->
                    <div class="form-group">
                        <label class="form-label">Files *</label>
                        <div class="upload-area"
                             @drop.prevent="handleDrop($event)"
                             @dragover.prevent="dragOver = true"
                             @dragleave.prevent="dragOver = false"
                             :class="{ 'drag-over': dragOver }">
                            <input type="file"
                                   id="file-input"
                                   x-ref="fileInput"
                                   multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip,.rar"
                                   @change="handleFileSelect($event)"
                                   class="file-input">

                            <div class="upload-content">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h4>Drop files here or click to browse</h4>
                                <p>Supported: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, TXT, CSV, ZIP, RAR</p>
                                <p>Maximum: 10 files, 10MB each</p>
                                <button type="button" class="btn btn-secondary" @click="$refs.fileInput.click()">
                                    <i class="fas fa-folder-open"></i>
                                    Browse Files
                                </button>
                            </div>
                        </div>
                        <div class="form-error" x-show="uploadErrors.files" x-text="uploadErrors.files"></div>
                    </div>

                    <!-- Selected Files List -->
                    <div x-show="selectedFiles.length > 0" class="selected-files">
                        <h5>Selected Files (<span x-text="selectedFiles.length"></span>)</h5>
                        <div class="files-list">
                            <template x-for="(file, index) in selectedFiles" :key="index">
                                <div class="file-item">
                                    <div class="file-info">
                                        <i :class="getFileIcon(getFileExtension(file.name))"></i>
                                        <div class="file-details">
                                            <div class="file-name" x-text="file.name"></div>
                                            <div class="file-size" x-text="formatFileSize(file.size)"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="remove-file" @click="removeFile(index)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Version and Comments -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-input" x-model="uploadForm.version" placeholder="1.0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Comments</label>
                            <textarea class="form-textarea" x-model="uploadForm.comments" placeholder="Optional comments about these files" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div x-show="uploading" class="upload-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" :style="`width: ${uploadProgress}%`"></div>
                        </div>
                        <div class="progress-text">
                            <span x-text="uploadProgress + '% uploaded'"></span>
                            <span x-text="uploadStatus"></span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeUploadModal()" :disabled="uploading">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="uploading || selectedFiles.length === 0">
                        <span x-show="!uploading">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Upload Files
                        </span>
                        <span x-show="uploading">
                            <i class="fas fa-spinner fa-spin"></i>
                            Uploading...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DOCUMENT VIEWER MODAL -->
    <div class="modal-overlay" x-show="showViewerModal" x-transition @click="closeViewerModal()">
        <div class="modal viewer-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="selectedDocument?.original_name || 'Document Viewer'"></h3>
                <div class="viewer-actions">
                    <button class="btn btn-sm btn-secondary" @click="downloadDocument(selectedDocument)">
                        <i class="fas fa-download"></i>
                        Download
                    </button>
                    <button class="modal-close" @click="closeViewerModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body viewer-body" x-show="selectedDocument">
                <div class="document-details">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>File Name:</label>
                            <span x-text="selectedDocument?.original_name"></span>
                        </div>
                        <div class="detail-item">
                            <label>File Size:</label>
                            <span x-text="formatFileSize(selectedDocument?.file_size)"></span>
                        </div>
                        <div class="detail-item">
                            <label>File Type:</label>
                            <span x-text="selectedDocument?.file_type?.toUpperCase()"></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge" :class="`status-${selectedDocument?.status}`" x-text="selectedDocument?.status?.toUpperCase()"></span>
                        </div>
                        <div class="detail-item">
                            <label>Uploaded By:</label>
                            <span x-text="selectedDocument?.uploaded_by?.name"></span>
                        </div>
                        <div class="detail-item">
                            <label>Upload Date:</label>
                            <span x-text="formatDate(selectedDocument?.created_at)"></span>
                        </div>
                        <div class="detail-item">
                            <label>PBC Request:</label>
                            <span x-text="selectedDocument?.pbc_request?.title"></span>
                        </div>
                        <div class="detail-item" x-show="selectedDocument?.version">
                            <label>Version:</label>
                            <span x-text="selectedDocument?.version"></span>
                        </div>
                    </div>

                    <div x-show="selectedDocument?.comments" class="document-comments">
                        <h5>Comments:</h5>
                        <p x-text="selectedDocument?.comments"></p>
                    </div>
                </div>

                <!-- File Preview -->
                <div class="document-preview">
                    <template x-if="isImageFile(selectedDocument?.file_type)">
                        <img :src="getDocumentUrl(selectedDocument)" :alt="selectedDocument?.original_name" class="preview-image">
                    </template>
                    <template x-if="!isImageFile(selectedDocument?.file_type)">
                        <div class="preview-placeholder">
                            <div class="file-icon-large" :class="getFileIconClass(selectedDocument?.file_type)">
                                <i :class="getFileIcon(selectedDocument?.file_type)"></i>
                            </div>
                            <p>Preview not available for this file type</p>
                            <button class="btn btn-primary" @click="downloadDocument(selectedDocument)">
                                <i class="fas fa-download"></i>
                                Download to View
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Approval Actions -->
                @if(auth()->user()->hasPermission('approve_document'))
                <div x-show="selectedDocument?.status === 'pending'" class="approval-section">
                    <h5>Document Review</h5>
                    <div class="approval-actions">
                        <button class="btn btn-success" @click="approveDocument(selectedDocument)">
                            <i class="fas fa-check"></i>
                            Approve Document
                        </button>
                        <button class="btn btn-danger" @click="rejectDocument(selectedDocument)">
                            <i class="fas fa-times"></i>
                            Reject Document
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- APPROVAL/REJECTION MODAL -->
    <div class="modal-overlay" x-show="showApprovalModal" x-transition @click="closeApprovalModal()">
        <div class="modal small-modal" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title" x-text="approvalAction === 'approve' ? 'Approve Document' : 'Reject Document'"></h3>
                <button class="modal-close" @click="closeApprovalModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="submitApproval()">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" x-text="approvalAction === 'approve' ? 'Comments (Optional)' : 'Rejection Reason (Required)'"></label>
                        <textarea
                            class="form-textarea"
                            x-model="approvalForm.comments"
                            :placeholder="approvalAction === 'approve' ? 'Optional approval comments...' : 'Please provide reason for rejection...'"
                            :required="approvalAction === 'reject'"
                            rows="4">
                        </textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeApprovalModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn" :class="approvalAction === 'approve' ? 'btn-success' : 'btn-danger'">
                        <i :class="approvalAction === 'approve' ? 'fas fa-check' : 'fas fa-times'"></i>
                        <span x-text="approvalAction === 'approve' ? 'Approve' : 'Reject'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Upload Center Styles */
    .upload-center-header {
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

    /* Storage Stats */
    .storage-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
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

    .stat-icon.total { background: #3B82F6; }
    .stat-icon.pending { background: #F59E0B; }
    .stat-icon.approved { background: #10B981; }
    .stat-icon.storage { background: #8B5CF6; }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1F2937;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #6B7280;
        font-weight: 500;
    }

    /* Common Button Styles */
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

    .btn-secondary {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #D1D5DB;
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
        grid-template-columns: 2fr repeat(4, 1fr) auto;
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

    /* Documents Section */
    .documents-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        overflow: hidden;
    }

    .section-header {
        padding: 1.5rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
    }

    .view-toggle {
        display: flex;
        background: #F3F4F6;
        border-radius: 8px;
        padding: 0.25rem;
    }

    .view-btn {
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #6B7280;
    }

    .view-btn.active {
        background: white;
        color: #3B82F6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Grid View */
    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .document-card {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .document-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .document-card.selected {
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    .document-header {
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #F3F4F6;
    }

    .file-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
    }

    .file-icon.pdf { background: #DC2626; }
    .file-icon.doc, .file-icon.docx { background: #2563EB; }
    .file-icon.xls, .file-icon.xlsx { background: #059669; }
    .file-icon.ppt, .file-icon.pptx { background: #DC2626; }
    .file-icon.jpg, .file-icon.jpeg, .file-icon.png, .file-icon.gif { background: #7C3AED; }
    .file-icon.txt { background: #6B7280; }
    .file-icon.csv { background: #059669; }
    .file-icon.zip, .file-icon.rar { background: #F59E0B; }
    .file-icon.default { background: #6B7280; }

    .document-actions {
        position: relative;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: #F3F4F6;
        border-radius: 6px;
        cursor: pointer;
        color: #6B7280;
        transition: all 0.2s ease;
    }

    .action-btn:hover {
        background: #E5E7EB;
        color: #374151;
    }

    .dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 160px;
        z-index: 50;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }

    .dropdown-menu.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: #374151;
        text-decoration: none;
        transition: background 0.2s ease;
        font-size: 0.9rem;
    }

    .dropdown-menu a:hover {
        background: #F3F4F6;
    }

    .document-preview {
        height: 200px;
        position: relative;
        overflow: hidden;
    }

    .file-thumbnail {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #F9FAFB;
    }

    .thumbnail-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .file-type-badge {
        padding: 1rem 2rem;
        background: #F3F4F6;
        border-radius: 8px;
        font-size: 1.2rem;
        font-weight: 600;
        color: #6B7280;
    }

    .document-info {
        padding: 1rem;
    }

    .document-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .document-size, .document-date, .document-uploader, .document-request {
        font-size: 0.8rem;
        color: #6B7280;
        margin-bottom: 0.25rem;
    }

    .document-status {
        margin: 0.5rem 0;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.status-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .status-badge.status-approved {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-badge.status-rejected {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* List View */
    .documents-table-container {
        overflow-x: auto;
    }

    .documents-table {
        width: 100%;
        border-collapse: collapse;
    }

    .documents-table th,
    .documents-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #E5E7EB;
    }

    .documents-table th {
        background: #F9FAFB;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
    }

    .documents-table tr:hover {
        background: #F9FAFB;
    }

    .documents-table tr.selected {
        background: #EBF8FF;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .file-icon-small {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: white;
        flex-shrink: 0;
    }

    .file-details {
        flex: 1;
    }

    .file-name {
        font-weight: 500;
        color: #1F2937;
        font-size: 0.9rem;
    }

    .file-type {
        font-size: 0.75rem;
        color: #6B7280;
    }

    .pbc-info, .user-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .pbc-title, .user-name {
        font-weight: 500;
        color: #1F2937;
        font-size: 0.9rem;
    }

    .pbc-category, .user-role {
        font-size: 0.75rem;
        color: #6B7280;
    }

    .date-cell, .size-cell {
        font-size: 0.9rem;
        color: #6B7280;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .approval-buttons {
        display: flex;
        gap: 0.25rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6B7280;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #D1D5DB;
    }

    .empty-state h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        margin-bottom: 2rem;
    }

    /* Pagination */
    .table-pagination {
        padding: 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pagination-info {
        font-size: 0.9rem;
        color: #6B7280;
    }

    .pagination-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination-btn {
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
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
        z-index: 50;
    }

    .bulk-actions-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        border: 1px solid #E5E7EB;
        padding: 1rem 1.5rem;
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
        gap: 0.75rem;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        padding: 2rem;
    }

    .modal {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 48px rgba(0, 0, 0, 0.2);
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .modal.small-modal {
        max-width: 500px;
    }

    .modal.viewer-modal {
        max-width: 1000px;
    }

    .modal.upload-modal {
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
        font-size: 1.2rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
    }

    .viewer-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border: none;
        background: #F3F4F6;
        border-radius: 6px;
        cursor: pointer;
        color: #6B7280;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background: #E5E7EB;
        color: #374151;
    }

    .modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1;
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-error {
        margin-top: 0.5rem;
        font-size: 0.8rem;
        color: #EF4444;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1rem;
    }

    /* Upload Area */
    .upload-area {
        border: 2px dashed #D1D5DB;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .upload-area:hover, .upload-area.drag-over {
        border-color: #3B82F6;
        background: #F0F9FF;
    }

    .file-input {
        display: none;
    }

    .upload-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .upload-icon {
        font-size: 3rem;
        color: #9CA3AF;
    }

    .upload-content h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }

    .upload-content p {
        color: #6B7280;
        margin: 0;
        font-size: 0.9rem;
    }

    /* Selected Files */
    .selected-files {
        background: #F9FAFB;
        border-radius: 8px;
        padding: 1rem;
    }

    .selected-files h5 {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
    }

    .files-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid #E5E7EB;
    }

    .file-item .file-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
    }

    .remove-file {
        width: 24px;
        height: 24px;
        border: none;
        background: #FEE2E2;
        color: #DC2626;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .remove-file:hover {
        background: #FECACA;
    }

    /* Upload Progress */
    .upload-progress {
        background: #F9FAFB;
        border-radius: 8px;
        padding: 1rem;
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #E5E7EB;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #3B82F6, #1D4ED8);
        transition: width 0.3s ease;
    }

    .progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #6B7280;
    }

    /* Document Details */
    .document-details {
        margin-bottom: 2rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .detail-item label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .detail-item span {
        font-size: 0.9rem;
        color: #374151;
    }

    .document-comments {
        background: #F9FAFB;
        border-radius: 8px;
        padding: 1rem;
    }

    .document-comments h5 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .document-comments p {
        font-size: 0.9rem;
        color: #6B7280;
        margin: 0;
        line-height: 1.5;
    }

    /* Document Preview */
    .document-preview {
        margin-bottom: 2rem;
    }

    .preview-image {
        width: 100%;
        max-height: 400px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #E5E7EB;
    }

    .preview-placeholder {
        text-align: center;
        padding: 3rem;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        background: #F9FAFB;
    }

    .file-icon-large {
        width: 80px;
        height: 80px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        margin: 0 auto 1rem;
    }

    .preview-placeholder p {
        color: #6B7280;
        margin-bottom: 1.5rem;
    }

    /* Approval Section */
    .approval-section {
        background: #F9FAFB;
        border-radius: 8px;
        padding: 1.5rem;
    }

    .approval-section h5 {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
    }

    .approval-actions {
        display: flex;
        gap: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .storage-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .filters-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .documents-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .upload-center-header {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .header-actions {
            justify-content: flex-start;
        }

        .storage-stats {
            grid-template-columns: 1fr;
        }

        .documents-grid {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .documents-table-container {
            overflow-x: scroll;
        }

        .modal {
            margin: 1rem;
            max-height: calc(100vh - 2rem);
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .bulk-actions-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .bulk-actions-buttons {
            justify-content: center;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .upload-center-header {
            margin-bottom: 1rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }

        .documents-section {
            margin: 0 -1rem;
            border-radius: 0;
        }

        .upload-area {
            padding: 2rem 1rem;
        }

        .upload-content h4 {
            font-size: 1rem;
        }

        .approval-actions {
            flex-direction: column;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function uploadCenter() {
    return {
        // Data properties
        loading: false,
        documents: [],
        stats: {},
        pagination: {},
        selectedDocuments: [],
        activeDropdown: null,
        viewMode: 'grid',
        dragOver: false,

        // Filter properties
        filters: {
            search: '',
            status: '',
            file_type: '',
            pbc_request_id: '',
            uploaded_by: '',
            date_from: '',
            date_to: '',
            sort_by: 'created_at',
            sort_order: 'desc',
            per_page: 25
        },

        // Modal states
        showUploadModal: false,
        showViewerModal: false,
        showApprovalModal: false,
        selectedDocument: null,
        approvalAction: null,

        // Upload form
        uploadForm: {
            pbc_request_id: '',
            version: '1.0',
            comments: ''
        },
        selectedFiles: [],
        uploading: false,
        uploadProgress: 0,
        uploadStatus: '',
        uploadErrors: {},

        // Approval form
        approvalForm: {
            comments: ''
        },

        // Data sources
        availablePbcRequests: [],
        availableUsers: [],

        // Initialize component
        async init() {
            await this.loadInitialData();
            this.setupEventListeners();
        },

        async loadInitialData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.loadDocuments(),
                    this.loadStats(),
                    this.loadPbcRequests(),
                    this.loadUsers()
                ]);
            } catch (error) {
                console.error('Failed to load initial data:', error);
                this.showNotification('Failed to load data', 'error');
            } finally {
                this.loading = false;
            }
        },

        setupEventListeners() {
            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.dropdown')) {
                    this.activeDropdown = null;
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
                if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                    if (this.hasPermission('upload_document')) {
                        this.openUploadModal();
                    }
                }
            });
        },

        // API calls
        async loadDocuments() {
            try {
                const response = await fetch('/api/v1/pbc-documents?' + new URLSearchParams(this.filters));
                const data = await response.json();

                if (data.success) {
                    this.documents = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
                } else {
                    throw new Error(data.message || 'Failed to load documents');
                }
            } catch (error) {
                console.error('Error loading documents:', error);
                this.showNotification('Failed to load documents', 'error');
            }
        },

        async loadStats() {
            try {
                const response = await fetch('/api/v1/pbc-documents/stats');
                const data = await response.json();

                if (data.success) {
                    this.stats = data.data;
                } else {
                    throw new Error(data.message || 'Failed to load stats');
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async loadPbcRequests() {
            try {
                const response = await fetch('/api/v1/pbc-requests?per_page=1000');
                const data = await response.json();

                if (data.success) {
                    this.availablePbcRequests = data.data.data || [];
                }
            } catch (error) {
                console.error('Error loading PBC requests:', error);
            }
        },

        async loadUsers() {
            try {
                const response = await fetch('/api/v1/users?per_page=1000');
                const data = await response.json();

                if (data.success) {
                    this.availableUsers = data.data.data || [];
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        },

        // Document actions
        viewDocument(document) {
            this.selectedDocument = document;
            this.showViewerModal = true;
        },

        async downloadDocument(document) {
            try {
                const response = await fetch(`/api/pbc-documents/${document.id}/download`);

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = document.original_name;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    throw new Error('Download failed');
                }
            } catch (error) {
                console.error('Error downloading document:', error);
                this.showNotification('Failed to download document', 'error');
            }
        },

        approveDocument(document) {
            this.selectedDocument = document;
            this.approvalAction = 'approve';
            this.approvalForm.comments = '';
            this.showApprovalModal = true;
        },

        rejectDocument(document) {
            this.selectedDocument = document;
            this.approvalAction = 'reject';
            this.approvalForm.comments = '';
            this.showApprovalModal = true;
        },

        async submitApproval() {
            if (!this.selectedDocument) return;

            try {
                const endpoint = this.approvalAction === 'approve' ? 'approve' : 'reject';
                const payload = this.approvalAction === 'approve'
                    ? { comments: this.approvalForm.comments }
                    : { reason: this.approvalForm.comments };

                const response = await fetch(`/api/pbc-documents/${this.selectedDocument.id}/${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification(
                        `Document ${this.approvalAction}d successfully`,
                        'success'
                    );
                    this.closeApprovalModal();
                    await this.loadDocuments();
                    await this.loadStats();
                } else {
                    throw new Error(data.message || `Failed to ${this.approvalAction} document`);
                }
            } catch (error) {
                console.error(`Error ${this.approvalAction}ing document:`, error);
                this.showNotification(`Failed to ${this.approvalAction} document`, 'error');
            }
        },

        async deleteDocument(document) {
            if (!confirm(`Are you sure you want to delete "${document.original_name}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/pbc-documents/${document.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Document deleted successfully', 'success');
                    await this.loadDocuments();
                    await this.loadStats();
                } else {
                    throw new Error(data.message || 'Failed to delete document');
                }
            } catch (error) {
                console.error('Error deleting document:', error);
                this.showNotification('Failed to delete document', 'error');
            }
        },

        // Bulk actions
        toggleDocumentSelection(documentId) {
            const index = this.selectedDocuments.indexOf(documentId);
            if (index > -1) {
                this.selectedDocuments.splice(index, 1);
            } else {
                this.selectedDocuments.push(documentId);
            }
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedDocuments = this.documents.map(doc => doc.id);
            } else {
                this.selectedDocuments = [];
            }
        },

        clearSelection() {
            this.selectedDocuments = [];
        },

        async bulkApprove() {
            if (!confirm(`Approve ${this.selectedDocuments.length} selected documents?`)) {
                return;
            }

            try {
                const response = await fetch('/api/v1/pbc-documents/bulk-approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document_ids: this.selectedDocuments,
                        comments: ''
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Documents approved successfully', 'success');
                    this.clearSelection();
                    await this.loadDocuments();
                    await this.loadStats();
                } else {
                    throw new Error(data.message || 'Failed to approve documents');
                }
            } catch (error) {
                console.error('Error approving documents:', error);
                this.showNotification('Failed to approve documents', 'error');
            }
        },

        async bulkReject() {
            const reason = prompt('Please provide a reason for rejecting these documents:');
            if (!reason) return;

            try {
                const response = await fetch('/api/v1/pbc-documents/bulk-reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document_ids: this.selectedDocuments,
                        reason: reason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Documents rejected successfully', 'success');
                    this.clearSelection();
                    await this.loadDocuments();
                    await this.loadStats();
                } else {
                    throw new Error(data.message || 'Failed to reject documents');
                }
            } catch (error) {
                console.error('Error rejecting documents:', error);
                this.showNotification('Failed to reject documents', 'error');
            }
        },

        async bulkDownload() {
            try {
                const response = await fetch('/api/v1/pbc-documents/bulk-download', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document_ids: this.selectedDocuments
                    })
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `documents_${new Date().toISOString().split('T')[0]}.zip`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    this.showNotification('Documents downloaded successfully', 'success');
                } else {
                    throw new Error('Download failed');
                }
            } catch (error) {
                console.error('Error downloading documents:', error);
                this.showNotification('Failed to download documents', 'error');
            }
        },

        async bulkDelete() {
            if (!confirm(`Delete ${this.selectedDocuments.length} selected documents? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch('/api/v1/pbc-documents/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document_ids: this.selectedDocuments
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Documents deleted successfully', 'success');
                    this.clearSelection();
                    await this.loadDocuments();
                    await this.loadStats();
                } else {
                    throw new Error(data.message || 'Failed to delete documents');
                }
            } catch (error) {
                console.error('Error deleting documents:', error);
                this.showNotification('Failed to delete documents', 'error');
            }
        },

        // Upload functionality
        openUploadModal() {
            this.resetUploadForm();
            this.showUploadModal = true;
        },

        closeUploadModal() {
            this.showUploadModal = false;
            this.resetUploadForm();
        },

        resetUploadForm() {
            this.uploadForm = {
                pbc_request_id: '',
                version: '1.0',
                comments: ''
            };
            this.selectedFiles = [];
            this.uploading = false;
            this.uploadProgress = 0;
            this.uploadStatus = '';
            this.uploadErrors = {};
            this.dragOver = false;
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.addFiles(files);
        },

        handleDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.addFiles(files);
        },

        addFiles(files) {
            const allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv', 'zip', 'rar'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            const maxFiles = 10;

            files.forEach(file => {
                // Check file count
                if (this.selectedFiles.length >= maxFiles) {
                    this.showNotification(`Maximum ${maxFiles} files allowed`, 'warning');
                    return;
                }

                // Check file size
                if (file.size > maxSize) {
                    this.showNotification(`File "${file.name}" exceeds 10MB limit`, 'warning');
                    return;
                }

                // Check file type
                const extension = this.getFileExtension(file.name).toLowerCase();
                if (!allowedTypes.includes(extension)) {
                    this.showNotification(`File type "${extension}" not supported`, 'warning');
                    return;
                }

                // Check for duplicates
                if (this.selectedFiles.some(f => f.name === file.name)) {
                    this.showNotification(`File "${file.name}" already selected`, 'warning');
                    return;
                }

                this.selectedFiles.push(file);
            });
        },

        removeFile(index) {
            this.selectedFiles.splice(index, 1);
        },

        async uploadFiles() {
            if (!this.uploadForm.pbc_request_id || this.selectedFiles.length === 0) {
                this.showNotification('Please select PBC request and files', 'warning');
                return;
            }

            this.uploading = true;
            this.uploadProgress = 0;
            this.uploadStatus = 'Preparing upload...';
            this.uploadErrors = {};

            try {
                const formData = new FormData();
                formData.append('pbc_request_id', this.uploadForm.pbc_request_id);
                formData.append('version', this.uploadForm.version);
                formData.append('comments', this.uploadForm.comments);

                this.selectedFiles.forEach((file, index) => {
                    formData.append(`files[${index}]`, file);
                });

                const response = await fetch('/api/v1/pbc-documents', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                // Simulate progress
                const progressInterval = setInterval(() => {
                    if (this.uploadProgress < 90) {
                        this.uploadProgress += Math.random() * 20;
                        this.uploadStatus = `Uploading files... ${Math.floor(this.uploadProgress)}%`;
                    }
                }, 200);

                const data = await response.json();
                clearInterval(progressInterval);

                this.uploadProgress = 100;
                this.uploadStatus = 'Upload complete!';

                if (data.success) {
                    setTimeout(() => {
                        this.showNotification('Files uploaded successfully', 'success');
                        this.closeUploadModal();
                        this.loadDocuments();
                        this.loadStats();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Error uploading files:', error);
                this.uploadErrors = error.errors || {};
                this.showNotification('Failed to upload files', 'error');
                this.uploading = false;
                this.uploadProgress = 0;
                this.uploadStatus = '';
            }
        },

        // Modal management
        closeViewerModal() {
            this.showViewerModal = false;
            this.selectedDocument = null;
        },

        closeApprovalModal() {
            this.showApprovalModal = false;
            this.selectedDocument = null;
            this.approvalAction = null;
            this.approvalForm.comments = '';
        },

        closeAllModals() {
            this.showUploadModal = false;
            this.showViewerModal = false;
            this.showApprovalModal = false;
            this.selectedDocument = null;
            this.approvalAction = null;
        },

        // UI helpers
        toggleDropdown(documentId) {
            this.activeDropdown = this.activeDropdown === documentId ? null : documentId;
        },

        getFileIcon(fileType) {
            const icons = {
                pdf: 'fas fa-file-pdf',
                doc: 'fas fa-file-word',
                docx: 'fas fa-file-word',
                xls: 'fas fa-file-excel',
                xlsx: 'fas fa-file-excel',
                ppt: 'fas fa-file-powerpoint',
                pptx: 'fas fa-file-powerpoint',
                jpg: 'fas fa-file-image',
                jpeg: 'fas fa-file-image',
                png: 'fas fa-file-image',
                gif: 'fas fa-file-image',
                txt: 'fas fa-file-alt',
                csv: 'fas fa-file-csv',
                zip: 'fas fa-file-archive',
                rar: 'fas fa-file-archive'
            };
            return icons[fileType?.toLowerCase()] || 'fas fa-file';
        },

        getFileIconClass(fileType) {
            return fileType?.toLowerCase() || 'default';
        },

        isImageFile(fileType) {
            return ['jpg', 'jpeg', 'png', 'gif'].includes(fileType?.toLowerCase());
        },

        getFileExtension(filename) {
            return filename.split('.').pop().toLowerCase();
        },

        getDocumentUrl(document) {
            return document.cloud_url || `/storage/${document.file_path}`;
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        },

        truncateText(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        },

        // Pagination
        get visiblePages() {
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const delta = 2;
            const range = [];
            const rangeWithDots = [];

            for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                range.push(i);
            }

            if (current - delta > 2) {
                rangeWithDots.push(1, '...');
            } else {
                rangeWithDots.push(1);
            }

            rangeWithDots.push(...range);

            if (current + delta < last - 1) {
                rangeWithDots.push('...', last);
            } else {
                if (last > 1) rangeWithDots.push(last);
            }

            return rangeWithDots;
        },

        changePage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.filters.page = page;
            this.loadDocuments();
        },

        // Utility functions
        clearFilters() {
            this.filters = {
                search: '',
                status: '',
                file_type: '',
                pbc_request_id: '',
                uploaded_by: '',
                date_from: '',
                date_to: '',
                sort_by: 'created_at',
                sort_order: 'desc',
                per_page: 25
            };
            this.loadDocuments();
        },

        async refreshDocuments() {
            await this.loadDocuments();
            await this.loadStats();
            this.showNotification('Documents refreshed', 'success');
        },

        hasPermission(permission) {
            // This should be implemented based on your auth system
            // For now, return true as a placeholder
            return true;
        },

        showNotification(message, type = 'info') {
            // Implementation depends on your notification system
            // This is a simple alert for demonstration
            if (type === 'error') {
                alert('Error: ' + message);
            } else if (type === 'success') {
                alert('Success: ' + message);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endpush
@endsection
