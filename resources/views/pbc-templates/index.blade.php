@extends('layouts.app')

@section('title', 'PBC Request Template')
@section('page-title', 'PBC Request Management')
@section('page-subtitle', 'Manage document requests, track submissions, and monitor progress')

@section('content')
<div x-data="pbcTemplateManagement()" x-init="init()">
    <!-- HEADER -->
    <div class="template-header">
        <h2>PBC Request Template</h2>
    </div>

    <!-- TEMPLATE CARD -->
    <div class="template-card">
        <!-- TEMPLATE HEADER -->
        <div class="template-card-header">
            <div class="template-logo">

            </div>
            <div class="template-title">
                <h3>Audit Requirement Checklist</h3>
            </div>
            <div class="template-code">

            </div>
        </div>

        <!-- TEMPLATE INFO SECTION -->
        <div class="template-info-section">
            <div class="info-grid">
                <div class="info-group">
                    <div class="info-row">
                        <label>Client:</label>
                        <input type="text" class="info-input" value="XYZ Limited" readonly>
                    </div>
                    <div class="info-row">
                        <label>Audit Period:</label>
                        <input type="text" class="info-input" value="2024-12-31" readonly>
                    </div>
                    <div class="info-row">
                        <label>Contact Person:</label>
                        <input type="text" class="info-input" value="James Martinez" readonly>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <input type="text" class="info-input" value="john.smith@xyzlimited.com" readonly>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-row">
                        <label>Engagement Partner:</label>
                        <input type="text" class="info-input" value="Maria Garcia" readonly>
                    </div>
                    <div class="info-row">
                        <label>Engagement Manager:</label>
                        <input type="text" class="info-input" value="Carlos Reyes" readonly>
                    </div>
                    <div class="info-row">
                        <label>Document Date:</label>
                        <input type="text" class="info-input" value="07/07/2025" readonly>
                    </div>
                    <div class="info-row">
                        <label>Percentage of Completion:</label>
                        <input type="text" class="info-input" value="23%" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHECKLIST TABLE -->
        <div class="checklist-section">
            <div class="table-container">
                <table class="checklist-table">
                    <thead>
                        <tr>
                            <th class="particulars-col">Particulars</th>
                            <th class="date-col">Date Requested</th>
                            <th class="assigned-col">Assigned To</th>
                            <th class="due-date-col">Due Date</th>
                            <th class="requested-col">Requested by</th>
                            <th class="status-col">Status</th>
                            <th class="files-col">Files</th>
                            <th class="remarks-col">Remarks</th>
                        </tr>
                    </thead>
                    <tbody x-data="checklistItems()">
                        <!-- Section Header -->
                        <tr class="section-header">
                            <td colspan="8">
                                <div class="section-header-content">
                                    <div class="section-title-text">1. Permanent File</div>
                                </div>
                            </td>
                        </tr>

                        <!-- Checklist Items -->
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="checklist-item">
                                <td class="particulars-cell">
                                    <div class="particulars-row">
                                        <span class="item-number" x-text="(index + 1) + '.'"></span>
                                        <div class="particulars-content">
                                            <div class="particulars-text" x-text="item.description"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="date-cell">
                                    <div class="date-display" x-text="item.date"></div>
                                </td>
                                <td class="assigned-cell">
                                    <div class="assigned-to" x-text="item.assignedTo"></div>
                                </td>
                                <td class="due-date-cell">
                                    <div class="due-date-display" x-text="item.dueDate"></div>
                                </td>
                                <td class="requested-cell">
                                    <div class="requested-by" x-text="item.requestedBy"></div>
                                </td>
                                <td class="status-cell">
                                    <div class="status-display" :class="'status-' + item.status" x-text="item.status.charAt(0).toUpperCase() + item.status.slice(1)"></div>
                                </td>
                                <td class="files-cell">
                                    <div class="file-section" x-show="item.hasFiles">
                                        <div class="file-list">
                                            <template x-for="(file, fileIndex) in item.files" :key="file.name">
                                                <div class="file-item">
                                                    <div class="file-info">
                                                        <i class="fas fa-file-pdf file-icon" x-show="file.type === 'pdf'"></i>
                                                        <i class="fas fa-file-excel file-icon" x-show="file.type === 'excel'"></i>
                                                        <i class="fas fa-file-word file-icon" x-show="file.type === 'word'"></i>
                                                        <div class="file-details">
                                                            <a href="#" class="file-name-link" @click.prevent="viewFile(file.name)" x-text="file.name"></a>
                                                            <span class="file-size" x-text="file.size"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="no-files" x-show="!item.hasFiles">
                                        <span class="no-files-text">No files attached</span>
                                    </div>
                                </td>
                                <td class="remarks-cell">
                                    <div class="file-remarks-section" x-show="item.hasFiles">
                                        <div class="file-remarks-list">
                                            <template x-for="(file, fileIndex) in item.files" :key="file.name + '_remarks'">
                                                <div class="file-remarks-item">
                                                    <div class="file-remarks-header">
                                                        <span class="file-remarks-name" x-text="file.name.substring(0, 20) + (file.name.length > 20 ? '...' : '')"></span>
                                                    </div>
                                                    <div class="file-remarks-content" x-text="file.remarks"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="no-remarks" x-show="!item.hasFiles">
                                        <span class="no-remarks-text">-</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Continue indicator -->

                    </tbody>
                </table>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="template-actions">
            <button class="btn btn-secondary btn-lg" @click="goBack()">
                <i class="fas fa-arrow-left"></i>
                Back
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Template Header */
    .template-header {
        margin-bottom: 2rem;
    }

    .template-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1F2937;
    }

    /* Template Card */
    .template-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #E5E7EB;
        overflow: hidden;
        margin-bottom: 2rem;
    }

    /* Template Card Header */
    .template-card-header {
        display: grid;
        grid-template-columns: 100px 1fr 120px;
        align-items: center;
        padding: 2rem;
        border-bottom: 2px solid #E5E7EB;
        background: #F9FAFB;
    }

    .template-logo .logo-placeholder {
        width: 80px;
        height: 60px;
        background: #E5E7EB;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6B7280;
        font-size: 0.9rem;
    }

    .template-title {
        text-align: center;
    }

    .template-title h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1F2937;
        margin: 0;
    }

    .template-code {
        text-align: right;
    }

    .code-badge {
        background: #1F2937;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-size: 1.25rem;
        font-weight: 700;
        display: inline-block;
    }

    /* Template Info Section */
    .template-info-section {
        padding: 2rem;
        border-bottom: 1px solid #E5E7EB;
        background: #F9FAFB;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .info-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .info-row label {
        font-weight: 600;
        color: #374151;
        min-width: 140px;
        flex-shrink: 0;
    }

    .info-input {
        flex: 1;
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 0.9rem;
        background: #F9FAFB;
        color: #6B7280;
    }

    /* Checklist Section */
    .checklist-section {
        padding: 2rem;
    }

    .table-container {
        overflow-x: auto;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
    }

    .checklist-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1400px;
        font-size: 0.85rem;
    }

    .checklist-table th {
        background: #F3F4F6;
        color: #374151;
        font-weight: 600;
        padding: 1rem 0.75rem;
        text-align: left;
        border-bottom: 2px solid #E5E7EB;
        border-right: 1px solid #E5E7EB;
        vertical-align: top;
        line-height: 1.3;
    }

    .checklist-table th:last-child {
        border-right: none;
    }

    /* Column widths */
    .particulars-col { width: 20%; }
    .date-col { width: 10%; }
    .assigned-col { width: 10%; }
    .due-date-col { width: 10%; }
    .requested-col { width: 10%; }
    .status-col { width: 8%; }
    .files-col { width: 20%; }
    .remarks-col { width: 12%; }

    /* Table Body */
    .checklist-table tbody tr {
        border-bottom: 1px solid #F3F4F6;
    }

    .checklist-table tbody tr:hover {
        background: #F9FAFB;
    }

    .checklist-table td {
        padding: 0.75rem;
        border-right: 1px solid #F3F4F6;
        vertical-align: top;
    }

    .checklist-table td:last-child {
        border-right: none;
    }

    /* Section Header */
    .section-header td {
        background: #EFF6FF;
        border-bottom: 2px solid #DBEAFE;
        padding: 1rem 0.75rem;
    }

    .section-header-content {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding-left: 1rem;
    }

    .section-title-text {
        font-weight: 600;
        color: #1E40AF;
        font-size: 1rem;
    }

    /* Checklist Items */
    .particulars-cell {
        padding: 0.75rem;
    }

    .particulars-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .item-number {
        font-weight: 600;
        color: #374151;
        flex-shrink: 0;
        margin-top: 0.25rem;
        min-width: 20px;
    }

    .particulars-content {
        flex: 1;
    }

    .particulars-text {
        color: #374151;
        line-height: 1.5;
        font-size: 0.9rem;
    }

    /* Display Elements */
    .date-display, .assigned-to, .due-date-display, .requested-by {
        color: #374151;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .status-display {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        text-align: center;
        text-transform: capitalize;
    }

    .status-display.status-completed {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-display.status-pending {
        background: #FEF3C7;
        color: #92400E;
    }

    .status-display.status-overdue {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* File Section */
    .file-section {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .file-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .file-item {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
    }

    .file-item:hover {
        background: #F3F4F6;
        border-color: #D1D5DB;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        min-width: 0;
    }

    .file-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .file-icon.fa-file-pdf {
        color: #DC2626;
    }

    .file-icon.fa-file-excel {
        color: #059669;
    }

    .file-icon.fa-file-word {
        color: #2563EB;
    }

    .file-details {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        flex: 1;
        min-width: 0;
    }

    .file-name-link {
        color: #2563EB;
        text-decoration: underline;
        font-weight: 500;
        font-size: 0.8rem;
        cursor: pointer;
        transition: color 0.3s ease;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.2;
    }

    .file-name-link:hover {
        color: #1D4ED8;
        text-decoration: underline;
    }

    .file-size {
        color: #6B7280;
        font-size: 0.7rem;
        line-height: 1;
    }

    .no-files {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9CA3AF;
        font-style: italic;
        font-size: 0.8rem;
        padding: 1rem;
    }

    /* File Remarks Section */
    .file-remarks-section {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .file-remarks-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .file-remarks-item {
        padding: 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }

    .file-remarks-header {
        margin-bottom: 0.5rem;
    }

    .file-remarks-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: #374151;
        display: block;
    }

    .file-remarks-content {
        font-size: 0.8rem;
        color: #6B7280;
        line-height: 1.3;
    }

    .no-remarks {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9CA3AF;
        font-style: italic;
        font-size: 0.8rem;
        padding: 1rem;
    }

    /* Continue Row */
    .continue-row td {
        text-align: center;
        font-style: italic;
        color: #6B7280;
        padding: 1.5rem;
        background: #F9FAFB;
    }

    /* Template Actions */
    .template-actions {
        padding: 2rem;
        background: #F9FAFB;
        border-top: 1px solid #E5E7EB;
        display: flex;
        justify-content: flex-start;
        gap: 1rem;
    }

    .btn-lg {
        padding: 1rem 2rem;
        font-size: 1rem;
        font-weight: 600;
    }

    /* Buttons */
    .btn {
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-secondary {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #D1D5DB;
    }

    .btn-secondary:hover {
        background: #E5E7EB;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .template-card-header {
            grid-template-columns: 80px 1fr 100px;
            padding: 1.5rem;
        }

        .template-title h3 {
            font-size: 1.25rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .checklist-section {
            padding: 1rem;
        }
    }

    @media (max-width: 768px) {
        .template-card-header {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 1rem;
        }

        .template-actions {
            flex-direction: column;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function pbcTemplateManagement() {
        return {
            // Initialize
            init() {
                console.log('🚀 PBC Template Management Init (View Only)');
            },

            // Actions
            goBack() {
                console.log('Going back to PBC requests...');
                window.location.href = '/pbc-requests';
            },

            // Alert system
            showAlert(message, type = 'info') {
                // Create alert element
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <div class="alert-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

    function checklistItems() {
        return {
            items: [
                {
                    description: 'Latest Articles of Incorporation and By-laws',
                    date: 'Jan 2, 2025',
                    assignedTo: 'Maria Garcia',
                    dueDate: 'Feb 15, 2025',
                    requestedBy: 'Carlos Reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'Articles_of_Incorporation.pdf', type: 'pdf', size: '2.4 MB', remarks: 'Document complete and properly signed.' },
                        { name: 'Company_Bylaws.pdf', type: 'pdf', size: '1.8 MB', remarks: 'Latest version received, no issues found.' }
                    ]
                },
                {
                    description: 'BIR Certificate of Registration',
                    date: 'Jan 2, 2025',
                    assignedTo: 'James Martinez',
                    dueDate: 'Feb 10, 2025',
                    requestedBy: 'Carlos Reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'BIR_Certificate_2024.pdf', type: 'pdf', size: '856 KB', remarks: 'Certificate is valid and current.' }
                    ]
                },
                {
                    description: 'Latest General Information Sheet filed with the SEC',
                    date: 'Jan 2, 2025',
                    assignedTo: 'Anna Thompson',
                    dueDate: 'Feb 20, 2025',
                    requestedBy: 'Carlos Reyes',
                    status: 'pending',
                    hasFiles: false,
                    files: []
                },
                {
                    description: 'Stock transfer book',
                    date: 'Jan 2, 2025',
                    assignedTo: 'David Wilson',
                    dueDate: 'Feb 12, 2025',
                    requestedBy: 'Carlos Reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'Stock_Transfer_Book_2024.xlsx', type: 'excel', size: '1.2 MB', remarks: 'Complete records, minor formatting needed.' },
                        { name: 'Stock_Certificates_Register.pdf', type: 'pdf', size: '3.1 MB', remarks: 'All certificates accounted for.' }
                    ]
                },
                {
                    description: 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period from January 1, 2024 to date.',
                    date: 'Jan 2, 2025',
                    assignedTo: 'Michelle Lopez',
                    dueDate: 'Jan 30, 2025',
                    requestedBy: 'Carlos Reyes',
                    status: 'overdue',
                    hasFiles: false,
                    files: []
                },
                {
                    description: 'Bank statements for all accounts for the year ended December 31, 2024',
                    date: 'Jan 5, 2025',
                    assignedTo: 'Kevin Taylor',
                    dueDate: 'Feb 25, 2025',
                    requestedBy: 'Anna Thompson',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'BDO_Statements_Q1_2024.pdf', type: 'pdf', size: '4.2 MB', remarks: 'Q1 statements complete and reconciled.' },
                        { name: 'BDO_Statements_Q2_2024.pdf', type: 'pdf', size: '3.8 MB', remarks: 'Q2 statements verified.' },
                        { name: 'BPI_Statements_Q3_2024.pdf', type: 'pdf', size: '4.1 MB', remarks: 'Q3 statements received, one discrepancy noted.' },
                        { name: 'BPI_Statements_Q4_2024.pdf', type: 'pdf', size: '4.5 MB', remarks: 'Q4 statements complete and accurate.' }
                    ]
                },
                {
                    description: 'Trial balance as at December 31, 2024',
                    date: 'Jan 8, 2025',
                    assignedTo: 'Amanda Clark',
                    dueDate: 'Feb 18, 2025',
                    requestedBy: 'David Wilson',
                    status: 'pending',
                    hasFiles: false,
                    files: []
                },
                {
                    description: 'Detailed general ledger for all accounts',
                    date: 'Jan 10, 2025',
                    assignedTo: 'Ryan Hall',
                    dueDate: 'Feb 28, 2025',
                    requestedBy: 'Michelle Lopez',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'General_Ledger_2024_Complete.xlsx', type: 'excel', size: '8.7 MB', remarks: 'Complete ledger file. Account coding verified and consistent with prior year.' }
                    ]
                }
            ],

            viewFile(fileName) {
                console.log(`Viewing file: ${fileName}`);
                this.showAlert(`Opening ${fileName} for preview`, 'info');
            },

            // Alert utility for checklist items
            showAlert(message, type = 'info') {
                // Use the parent component's showAlert method
                const parentComponent = document.querySelector('[x-data*="pbcTemplateManagement"]').__x;
                if (parentComponent && parentComponent.$data.showAlert) {
                    parentComponent.$data.showAlert(message, type);
                }
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
