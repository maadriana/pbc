{{-- Progress Details Modal Component --}}
<div class="progress-modal-overlay" x-show="showProgressModal" x-transition @click="closeProgressModal()">
    <div class="progress-modal" @click.stop>
        <!-- Modal Header -->
        <div class="progress-modal-header">
            <h3 class="progress-modal-title">
                <i class="fas fa-tasks text-blue-600"></i>
                Progress Details - <span x-text="selectedProject?.clientName || 'Project'"></span>
            </h3>
            <button class="progress-modal-close" @click="closeProgressModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="progress-modal-body">
            <!-- Project Summary -->
            <div class="project-summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Project</div>
                        <div class="summary-value" x-text="selectedProject?.title || ''"></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Template</div>
                        <div class="summary-value" x-text="selectedProject?.template || ''"></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Progress</div>
                        <div class="summary-value">
                            <span x-text="(selectedProject?.progressPercentage || 0) + '%'"></span>
                            <span class="progress-fraction" x-text="'(' + (selectedProject?.completedItems || 0) + '/' + (selectedProject?.totalItems || 0) + ')'"></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Status</div>
                        <div class="summary-value">
                            <span class="status-badge" :class="'status-' + (selectedProject?.status || 'pending')" x-text="(selectedProject?.status || 'pending').replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Categories -->
            <div class="progress-categories" x-data="progressModal()">
                <div class="category-tabs">
                    <button class="category-tab" :class="{ 'active': activeTab === 'completed' }" @click="switchTab('completed')">
                        <i class="fas fa-check-circle"></i>
                        <span>Completed</span>
                        <span class="tab-count" x-text="completedItems.length"></span>
                    </button>
                    <button class="category-tab" :class="{ 'active': activeTab === 'pending' }" @click="switchTab('pending')">
                        <i class="fas fa-clock"></i>
                        <span>Pending</span>
                        <span class="tab-count" x-text="pendingItems.length"></span>
                    </button>
                    <button class="category-tab" :class="{ 'active': activeTab === 'overdue' }" @click="switchTab('overdue')">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Overdue</span>
                        <span class="tab-count" x-text="overdueItems.length"></span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Completed Items -->
                    <div class="tab-pane" x-show="activeTab === 'completed'">
                        <div class="items-table-container">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th>Assigned To</th>
                                        <th>Date Requested</th>
                                        <th>Files</th>
                                        <th>Received Date</th>
                                        <th>Requested By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in completedItems" :key="index">
                                        <tr>
                                            <td>
                                                <div class="particulars-cell">
                                                    <i class="fas fa-check-circle text-green-600 item-status-icon"></i>
                                                    <span x-text="item.particulars"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="assignee-name" x-text="item.assignedTo"></span>
                                            </td>
                                            <td>
                                                <span class="date-text" x-text="item.dateRequested"></span>
                                            </td>
                                            <td>
                                                <div class="files-cell">
                                                    <template x-for="(file, fileIndex) in item.files" :key="fileIndex">
                                                        <div class="file-item-small">
                                                            <i class="fas fa-file-pdf file-icon-small" x-show="file.type === 'PDF'"></i>
                                                            <i class="fas fa-file-excel file-icon-small" x-show="file.type === 'XLSX'"></i>
                                                            <i class="fas fa-file-word file-icon-small" x-show="file.type === 'DOCX'"></i>
                                                            <div class="file-details-small">
                                                                <span class="file-name-small" x-text="file.name"></span>
                                                                <span class="file-date-small" x-text="'Submitted: ' + file.submitDate"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="date-text received-date" x-text="item.receivedDate"></span>
                                            </td>
                                            <td>
                                                <span class="requested-by-name" x-text="item.requestedBy"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Items -->
                    <div class="tab-pane" x-show="activeTab === 'pending'">
                        <!-- Bulk Actions for Pending -->
                        <div class="bulk-actions-header">
                            <button class="btn btn-sm btn-warning bulk-action-btn" @click="reminderAll('pending')" title="Send Reminder to All Pending Items">
                                <i class="fas fa-bell"></i>
                                Reminder All
                            </button>
                        </div>

                        <div class="items-table-container">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th>Assigned To</th>
                                        <th>Date Requested</th>
                                        <th>Due Date</th>
                                        <th>Requested By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in pendingItems" :key="index">
                                        <tr>
                                            <td>
                                                <div class="particulars-cell">
                                                    <i class="fas fa-clock text-yellow-600 item-status-icon"></i>
                                                    <span x-text="item.particulars"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="assignee-name" x-text="item.assignedTo"></span>
                                            </td>
                                            <td>
                                                <span class="date-text" x-text="item.dateRequested"></span>
                                            </td>
                                            <td>
                                                <span class="date-text due-date" x-text="item.dueDate"></span>
                                            </td>
                                            <td>
                                                <span class="requested-by-name" x-text="item.requestedBy"></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-warning reminder-btn" @click="sendReminder(item)" title="Send Reminder">
                                                    <i class="fas fa-bell"></i>
                                                    Reminder
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Overdue Items -->
                    <div class="tab-pane" x-show="activeTab === 'overdue'">
                        <!-- Bulk Actions for Overdue -->
                        <div class="bulk-actions-header">
                            <button class="btn btn-sm btn-danger bulk-action-btn" @click="urgentAll('overdue')" title="Send Urgent Reminder to All Overdue Items">
                                <i class="fas fa-exclamation-circle"></i>
                                Urgent All
                            </button>
                        </div>

                        <div class="items-table-container">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th>Assigned To</th>
                                        <th>Date Requested</th>
                                        <th>Days Outstanding</th>
                                        <th>Requested By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in overdueItems" :key="index">
                                        <tr>
                                            <td>
                                                <div class="particulars-cell">
                                                    <i class="fas fa-exclamation-triangle text-red-600 item-status-icon"></i>
                                                    <span x-text="item.particulars"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="assignee-name" x-text="item.assignedTo"></span>
                                            </td>
                                            <td>
                                                <span class="date-text" x-text="item.dateRequested"></span>
                                            </td>
                                            <td>
                                                <span class="overdue-badge" x-text="item.daysOutstanding + ' days'"></span>
                                            </td>
                                            <td>
                                                <span class="requested-by-name" x-text="item.requestedBy"></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-danger reminder-btn" @click="sendUrgentReminder(item)" title="Send Urgent Reminder">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    Urgent
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="progress-modal-footer">
            <button class="btn btn-secondary" @click="closeProgressModal()">
                <i class="fas fa-arrow-left"></i>
                Back
            </button>
            <button class="btn btn-primary" @click="generateDetailedReport()">
                <i class="fas fa-file-alt"></i>
                Generate Report
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Progress Modal Styles */
    .progress-modal-overlay {
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

    .progress-modal {
        background: white;
        border-radius: 16px;
        max-width: 1200px;
        width: 100%;
        max-height: 90vh;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .progress-modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #F9FAFB;
        flex-shrink: 0;
    }

    .progress-modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .progress-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #9CA3AF;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .progress-modal-close:hover {
        background: #F3F4F6;
        color: #6B7280;
    }

    .progress-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem 2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-height: 0;
    }

    /* Project Summary */
    .project-summary {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 1rem 1.5rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    .summary-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .summary-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .progress-fraction {
        font-size: 0.75rem;
        color: #6B7280;
        font-weight: 500;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: capitalize;
    }

    .status-badge.status-completed {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-badge.status-pending, .status-badge.status-in-progress {
        background: #FEF3C7;
        color: #92400E;
    }

    .status-badge.status-overdue {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* Progress Categories */
    .progress-categories {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        overflow: hidden;
    }

    .category-tabs {
        display: flex;
        background: #F9FAFB;
        border-bottom: 1px solid #E5E7EB;
    }

    .category-tab {
        flex: 1;
        padding: 1rem 1.5rem;
        border: none;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 500;
        color: #6B7280;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }

    .category-tab:hover {
        background: #F3F4F6;
        color: #374151;
    }

    .category-tab.active {
        background: white;
        color: #1F2937;
        border-bottom-color: #3B82F6;
    }

    .tab-count {
        background: #E5E7EB;
        color: #6B7280;
        padding: 0.125rem 0.5rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .category-tab.active .tab-count {
        background: #3B82F6;
        color: white;
    }

    /* Tab Content */
    .tab-content {
        padding: 1.5rem;
        min-height: 300px;
        max-height: 500px;
        overflow-y: auto;
    }

    /* Items Table */
    .items-table-container {
        overflow-x: auto;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
        font-size: 0.85rem;
    }

    .items-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.8rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .items-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: top;
        font-size: 0.8rem;
    }

    .items-table tbody tr:hover {
        background: #F9FAFB;
    }

    .items-table tbody tr:last-child td {
        border-bottom: none;
    }

    .particulars-cell {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        max-width: 300px;
    }

    .item-status-icon {
        font-size: 0.9rem;
        flex-shrink: 0;
        margin-top: 0.125rem;
    }

    .assignee-name {
        font-weight: 500;
        color: #374151;
    }

    .date-text {
        font-size: 0.8rem;
        color: #6B7280;
    }

    .due-date {
        color: #F59E0B;
        font-weight: 500;
    }

    .received-date {
        color: #059669;
        font-weight: 500;
    }

    .requested-by-name {
        font-weight: 500;
        color: #374151;
    }

    /* Bulk Actions Header */
    .bulk-actions-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 1rem;
        padding: 0 0.5rem;
    }

    .bulk-action-btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .bulk-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Files Cell */
    .files-cell {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-width: 200px;
    }

    .file-item-small {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
    }

    .file-icon-small {
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .file-icon-small.fa-file-pdf {
        color: #DC2626;
    }

    .file-icon-small.fa-file-excel {
        color: #059669;
    }

    .file-icon-small.fa-file-word {
        color: #2563EB;
    }

    .file-details-small {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        min-width: 0;
    }

    .file-name-small {
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .file-date-small {
        font-size: 0.65rem;
        color: #6B7280;
    }

    /* Remarks Cell - Per File */
    .remarks-cell {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-width: 150px;
    }

    .file-remark-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
    }

    .file-remark-name {
        font-size: 0.7rem;
        color: #6B7280;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .file-remark-status {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: capitalize;
        padding: 0.125rem 0.375rem;
        border-radius: 8px;
        text-align: center;
    }

    .file-remark-status.remark-good {
        background: #D1FAE5;
        color: #065F46;
    }

    .file-remark-status.remark-reject {
        background: #FEE2E2;
        color: #991B1B;
    }

    .file-remark-status.remark-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    /* Overdue Badge */
    .overdue-badge {
        padding: 0.25rem 0.5rem;
        background: #FEE2E2;
        color: #991B1B;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        text-align: center;
        display: inline-block;
    }

    /* Reminder Button */
    .reminder-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.7rem;
        border-radius: 4px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .reminder-btn:hover {
        transform: translateY(-1px);
    }

    .progress-modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #E5E7EB;
        background: #F9FAFB;
        display: flex;
        justify-content: space-between;
        flex-shrink: 0;
    }

    /* Utility classes */
    .text-blue-600 {
        color: #2563EB;
    }

    .text-green-600 {
        color: #059669;
    }

    .text-yellow-600 {
        color: #D97706;
    }

    .text-red-600 {
        color: #DC2626;
    }

    /* Custom Scrollbar */
    .tab-content::-webkit-scrollbar {
        width: 8px;
    }

    .tab-content::-webkit-scrollbar-track {
        background: #F3F4F6;
        border-radius: 4px;
    }

    .tab-content::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 4px;
    }

    .tab-content::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .progress-modal {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
            max-height: 95vh;
        }

        .progress-modal-header,
        .progress-modal-body,
        .progress-modal-footer {
            padding: 1rem;
        }

        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .category-tabs {
            flex-direction: column;
        }

        .progress-modal-footer {
            flex-direction: column;
            gap: 1rem;
        }
    }

    @media (max-width: 480px) {
        .progress-modal {
            max-height: 98vh;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }

        .tab-content {
            max-height: 250px;
            padding: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Progress Modal Data and Functions
    document.addEventListener('alpine:init', () => {
        Alpine.data('progressModal', () => ({
            activeTab: 'completed',
            completedItems: [],
            pendingItems: [],
            overdueItems: [],

            init() {
                // Initialize with sample data
                this.generateProgressData();
            },

            reminderAll(type) {
                console.log(`Sending reminder to all ${type} items`);
                const count = type === 'pending' ? this.pendingItems.length : 0;
                const parentComponent = document.querySelector('[x-data*="progressTrackerManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert(`Reminder sent to all ${count} pending items!`, 'success');
                }
            },

            urgentAll(type) {
                console.log(`Sending urgent reminder to all ${type} items`);
                const count = type === 'overdue' ? this.overdueItems.length : 0;
                const parentComponent = document.querySelector('[x-data*="progressTrackerManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert(`Urgent reminder sent to all ${count} overdue items!`, 'warning');
                }
            },

            switchTab(tab) {
                this.activeTab = tab;
                console.log('Switched to tab:', tab);
            },

            generateProgressData() {
                // Sample completed items with file-specific remarks
                this.completedItems = [
                    {
                        particulars: 'Latest Articles of Incorporation and By-laws',
                        assignedTo: 'Maria Garcia',
                        dateRequested: 'Jan 2, 2025',
                        files: [
                            { name: 'Articles_of_Incorporation.pdf', type: 'PDF', submitDate: 'Jul 6, 2025', remarkStatus: 'good' },
                            { name: 'Company_Bylaws.pdf', type: 'PDF', submitDate: 'Jul 6, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jul 8, 2025',
                        requestedBy: 'Carlos Reyes'
                    },
                    {
                        particulars: 'BIR Certificate of Registration',
                        assignedTo: 'James Martinez',
                        dateRequested: 'Jan 2, 2025',
                        files: [
                            { name: 'BIR_Certificate_2024.pdf', type: 'PDF', submitDate: 'Jul 5, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jul 7, 2025',
                        requestedBy: 'Anna Thompson'
                    },
                    {
                        particulars: 'Bank statements for all accounts for the year ended December 31, 2024',
                        assignedTo: 'Kevin Taylor',
                        dateRequested: 'Jan 5, 2025',
                        files: [
                            { name: 'BDO_Statements_Q1_2024.pdf', type: 'PDF', submitDate: 'Jul 3, 2025', remarkStatus: 'good' },
                            { name: 'BDO_Statements_Q2_2024.pdf', type: 'PDF', submitDate: 'Jul 3, 2025', remarkStatus: 'reject' },
                            { name: 'BPI_Statements_Q3_2024.pdf', type: 'PDF', submitDate: 'Jul 4, 2025', remarkStatus: 'good' },
                            { name: 'BPI_Statements_Q4_2024.pdf', type: 'PDF', submitDate: 'Jul 4, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jul 6, 2025',
                        requestedBy: 'David Wilson'
                    },
                    {
                        particulars: 'General ledger for all accounts',
                        assignedTo: 'Ryan Hall',
                        dateRequested: 'Jan 10, 2025',
                        files: [
                            { name: 'General_Ledger_2024_Complete.xlsx', type: 'XLSX', submitDate: 'Jul 1, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jul 5, 2025',
                        requestedBy: 'Michelle Lopez'
                    },
                    {
                        particulars: 'Trial balance as at December 31, 2024',
                        assignedTo: 'Amanda Clark',
                        dateRequested: 'Jan 8, 2025',
                        files: [
                            { name: 'Trial_Balance_2024.xlsx', type: 'XLSX', submitDate: 'Jun 30, 2025', remarkStatus: 'good' },
                            { name: 'Supporting_Schedules.docx', type: 'DOCX', submitDate: 'Jun 30, 2025', remarkStatus: 'reject' }
                        ],
                        receivedDate: 'Jul 4, 2025',
                        requestedBy: 'Kevin Taylor'
                    },
                    {
                        particulars: 'Fixed assets register with depreciation schedules',
                        assignedTo: 'Michelle Lopez',
                        dateRequested: 'Jan 12, 2025',
                        files: [
                            { name: 'Fixed_Assets_Register.xlsx', type: 'XLSX', submitDate: 'Jun 28, 2025', remarkStatus: 'good' },
                            { name: 'Depreciation_Schedules.pdf', type: 'PDF', submitDate: 'Jun 28, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jul 2, 2025',
                        requestedBy: 'Amanda Clark'
                    },
                    {
                        particulars: 'Accounts payable aging and vendor listing',
                        assignedTo: 'David Wilson',
                        dateRequested: 'Jan 15, 2025',
                        files: [
                            { name: 'AP_Aging_Report.xlsx', type: 'XLSX', submitDate: 'Jun 25, 2025', remarkStatus: 'reject' },
                            { name: 'Vendor_Master_List.pdf', type: 'PDF', submitDate: 'Jun 25, 2025', remarkStatus: 'good' }
                        ],
                        receivedDate: 'Jun 30, 2025',
                        requestedBy: 'Ryan Hall'
                    }
                ];

                // Sample pending items
                this.pendingItems = [
                    {
                        particulars: 'Latest General Information Sheet filed with SEC',
                        assignedTo: 'Anna Thompson',
                        dateRequested: 'Jan 2, 2025',
                        dueDate: 'Jul 15, 2025',
                        requestedBy: 'Carlos Reyes'
                    },
                    {
                        particulars: 'Stock transfer book and stockholder records',
                        assignedTo: 'David Wilson',
                        dateRequested: 'Jan 2, 2025',
                        dueDate: 'Jul 18, 2025',
                        requestedBy: 'Anna Thompson'
                    },
                    {
                        particulars: 'Insurance policies and coverage documentation',
                        assignedTo: 'Michelle Lopez',
                        dateRequested: 'Jan 12, 2025',
                        dueDate: 'Jul 20, 2025',
                        requestedBy: 'David Wilson'
                    },
                    {
                        particulars: 'Legal contracts and agreements for 2024',
                        assignedTo: 'Carlos Reyes',
                        dateRequested: 'Jan 18, 2025',
                        dueDate: 'Jul 22, 2025',
                        requestedBy: 'Michelle Lopez'
                    },
                    {
                        particulars: 'Inventory count reports and supporting schedules',
                        assignedTo: 'Amanda Clark',
                        dateRequested: 'Jan 20, 2025',
                        dueDate: 'Jul 25, 2025',
                        requestedBy: 'Kevin Taylor'
                    }
                ];

                // Sample overdue items
                this.overdueItems = [
                    {
                        particulars: 'Minutes of meetings of stockholders, board of directors, and executive committee',
                        assignedTo: 'Michelle Lopez',
                        dateRequested: 'Jan 2, 2025',
                        daysOutstanding: 12,
                        requestedBy: 'Carlos Reyes'
                    },
                    {
                        particulars: 'Accounts receivable aging report and collection procedures',
                        assignedTo: 'Carlos Reyes',
                        dateRequested: 'Jan 15, 2025',
                        daysOutstanding: 8,
                        requestedBy: 'Anna Thompson'
                    },
                    {
                        particulars: 'Payroll registers and employee benefit documentation',
                        assignedTo: 'Ryan Hall',
                        dateRequested: 'Jan 8, 2025',
                        daysOutstanding: 15,
                        requestedBy: 'David Wilson'
                    },
                    {
                        particulars: 'Tax returns and correspondence with BIR',
                        assignedTo: 'James Martinez',
                        dateRequested: 'Jan 10, 2025',
                        daysOutstanding: 6,
                        requestedBy: 'Michelle Lopez'
                    }
                ];
            },

            sendReminder(item) {
                console.log('Sending reminder for:', item.particulars);
                // Parent component's showAlert method
                const parentComponent = document.querySelector('[x-data*="progressTrackerManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert(`Reminder sent to ${item.assignedTo} for: ${item.particulars}`, 'success');
                }
            },

            sendUrgentReminder(item) {
                console.log('Sending urgent reminder for:', item.particulars);
                // Parent component's showAlert method
                const parentComponent = document.querySelector('[x-data*="progressTrackerManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert(`Urgent reminder sent to ${item.assignedTo} for overdue item: ${item.particulars}`, 'warning');
                }
            },

            generateDetailedReport() {
                console.log('Generating detailed progress report...');
                // Parent component's showAlert method
                const parentComponent = document.querySelector('[x-data*="progressTrackerManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert('Detailed progress report generated!', 'success');
                }
            }
        }));
    });
</script>
@endpush
