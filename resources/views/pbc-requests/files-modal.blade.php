{{-- PBC Files Modal Component --}}
<div class="files-modal-overlay" x-show="showFilesModal" x-transition @click="closeModal()">
    <div class="files-modal" @click.stop>
        <!-- Modal Header -->
        <div class="files-modal-header">
            <h3 class="files-modal-title">Uploaded Files from XYZ Limited</h3>
            <button class="files-modal-close" @click="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="filesUploaded Files from (Client Name (ex. ABC Corporation))-modal-body">
            <!-- New Files Section -->
            <div class="files-section">
                <div class="files-section-header">
                    <div class="section-title">
                        <i class="fas fa-file-upload section-icon new-files"></i>
                        <span class="section-text">New Files (3)</span>
                    </div>
                    <button class="btn btn-sm btn-success download-all-btn">
                        <i class="fas fa-download"></i>
                        Download All
                    </button>
                </div>

                <div class="files-table-container">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>File type</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Annual_Report_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>2.4 MB</td>
                                <td>Jul 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Financial_Statements_Q4.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>856 KB</td>
                                <td>Jul 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Board_Minutes_December.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>124 KB</td>
                                <td>Jul 7, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Received Files Section -->
            <div class="files-section">
                <div class="files-section-header">
                    <div class="section-title">
                        <i class="fas fa-check-circle section-icon received-files"></i>
                        <span class="section-text">Received Files (18)</span>
                    </div>
                </div>

                <div class="files-table-container">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>File type</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Tax_Returns_2023.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.8 MB</td>
                                <td>Jul 5, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Ledger_Summary_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>3.2 MB</td>
                                <td>Jul 4, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>BIR_Certificate.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>425 KB</td>
                                <td>Jul 3, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Corporate_Bylaws.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>89 KB</td>
                                <td>Jul 2, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>SEC_Registration.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>672 KB</td>
                                <td>Jul 1, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Trial_Balance_Q4_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>1.5 MB</td>
                                <td>Jun 30, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Bank_Statements_Dec_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>2.1 MB</td>
                                <td>Jun 28, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Audit_Adjustments_Summary.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>156 KB</td>
                                <td>Jun 25, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Insurance_Policies_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>890 KB</td>
                                <td>Jun 20, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Accounts_Receivable_Aging.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>734 KB</td>
                                <td>Jun 18, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Fixed_Assets_Register.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.2 MB</td>
                                <td>Jun 15, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Management_Letter_Response.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>203 KB</td>
                                <td>Jun 12, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Depreciation_Schedule_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>567 KB</td>
                                <td>Jun 10, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Payroll_Summary_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>945 KB</td>
                                <td>Jun 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Inventory_Count_Report.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.8 MB</td>
                                <td>Jun 5, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Contract_Agreements_Summary.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>178 KB</td>
                                <td>Jun 3, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="files-modal-footer">
            <button class="btn btn-secondary" @click="closeModal()">
                <i class="fas fa-arrow-left"></i>
                Back
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
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
        max-width: 1200px;
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

    .section-icon.new-files {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .section-icon.received-files {
        background: #D1FAE5;
        color: #065F46;
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
        max-height: 250px;
        border-top: 1px solid #E5E7EB;
    }

    .files-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
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

    .file-type.excel {
        background: #D1FAE5;
        color: #059669;
    }

    .file-type.word {
        background: #DBEAFE;
        color: #2563EB;
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

    /* Custom Scrollbar for webkit browsers */
    .files-modal-body::-webkit-scrollbar,
    .files-table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .files-modal-body::-webkit-scrollbar-track,
    .files-table-container::-webkit-scrollbar-track {
        background: #F3F4F6;
        border-radius: 4px;
    }

    .files-modal-body::-webkit-scrollbar-thumb,
    .files-table-container::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 4px;
    }

    .files-modal-body::-webkit-scrollbar-thumb:hover,
    .files-table-container::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }

    /* Responsive Design for Files Modal */
    @media (max-width: 768px) {
        .files-modal {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
            max-height: 95vh;
        }

        .files-modal-header,
        .files-modal-body,
        .files-modal-footer {
            padding: 1rem;
        }

        .files-section-header {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .files-table {
            font-size: 0.7rem;
        }

        .files-table th,
        .files-table td {
            padding: 0.5rem 0.75rem;
        }

        .files-table-container {
            max-height: 200px;
        }
    }

    @media (max-width: 480px) {
        .files-modal {
            max-height: 98vh;
        }

        .file-name-cell {
            min-width: 120px;
            max-width: 150px;
        }

        .file-name-cell span {
            max-width: 100px;
        }

        .files-table-container {
            max-height: 150px;
        }
    }
</style>
@endpush{{-- PBC Files Modal Component --}}
<div class="files-modal-overlay" x-show="showFilesModal" x-transition @click="closeModal()">
    <div class="files-modal" @click.stop>
        <!-- Modal Header -->
        <div class="files-modal-header">
            <h3 class="files-modal-title">Uploaded Files from XYZ Limited</h3>
            <button class="files-modal-close" @click="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="files-modal-body">
            <!-- New Files Section -->
            <div class="files-section">
                <div class="files-section-header">
                    <div class="section-title">
                        <i class="fas fa-file-upload section-icon new-files"></i>
                        <span class="section-text">New Files (3)</span>
                    </div>
                    <button class="btn btn-sm btn-success download-all-btn">
                        <i class="fas fa-download"></i>
                        Download All
                    </button>
                </div>

                <div class="files-table-container">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>File type</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Annual_Report_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>2.4 MB</td>
                                <td>Jul 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Financial_Statements_Q4.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>856 KB</td>
                                <td>Jul 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Board_Minutes_December.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>124 KB</td>
                                <td>Jul 7, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Received Files Section -->
            <div class="files-section">
                <div class="files-section-header">
                    <div class="section-title">
                        <i class="fas fa-check-circle section-icon received-files"></i>
                        <span class="section-text">Received Files (18)</span>
                    </div>
                </div>

                <div class="files-table-container">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>File type</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Tax_Returns_2023.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.8 MB</td>
                                <td>Jul 5, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Ledger_Summary_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>3.2 MB</td>
                                <td>Jul 4, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>BIR_Certificate.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>425 KB</td>
                                <td>Jul 3, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Corporate_Bylaws.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>89 KB</td>
                                <td>Jul 2, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>SEC_Registration.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>672 KB</td>
                                <td>Jul 1, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Trial_Balance_Q4_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>1.5 MB</td>
                                <td>Jun 30, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Bank_Statements_Dec_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>2.1 MB</td>
                                <td>Jun 28, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Audit_Adjustments_Summary.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>156 KB</td>
                                <td>Jun 25, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Insurance_Policies_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>890 KB</td>
                                <td>Jun 20, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Accounts_Receivable_Aging.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>734 KB</td>
                                <td>Jun 18, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Fixed_Assets_Register.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.2 MB</td>
                                <td>Jun 15, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Management_Letter_Response.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>203 KB</td>
                                <td>Jun 12, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Depreciation_Schedule_2024.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>567 KB</td>
                                <td>Jun 10, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-excel file-icon excel"></i>
                                        <span>Payroll_Summary_2024.xlsx</span>
                                    </div>
                                </td>
                                <td><span class="file-type excel">XLSX</span></td>
                                <td>945 KB</td>
                                <td>Jun 8, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-pdf file-icon pdf"></i>
                                        <span>Inventory_Count_Report.pdf</span>
                                    </div>
                                </td>
                                <td><span class="file-type pdf">PDF</span></td>
                                <td>1.8 MB</td>
                                <td>Jun 5, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-word file-icon word"></i>
                                        <span>Contract_Agreements_Summary.docx</span>
                                    </div>
                                </td>
                                <td><span class="file-type word">DOCX</span></td>
                                <td>178 KB</td>
                                <td>Jun 3, 2025</td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn btn-xs btn-secondary" title="View File">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="files-modal-footer">
            <button class="btn btn-secondary" @click="closeModal()">
                <i class="fas fa-arrow-left"></i>
                Back
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
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
        padding: 2rem;
    }

    .files-modal {
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

    .files-modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #F9FAFB;
    }

    .files-modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin: 0;
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
        max-height: calc(90vh - 160px);
    }

    .files-section {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        overflow: hidden;
    }

    .files-section-header {
        padding: 1rem 1.5rem;
        background: #F9FAFB;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    .section-icon.new-files {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .section-icon.received-files {
        background: #D1FAE5;
        color: #065F46;
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
        max-height: 300px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
    }

    .files-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
        font-size: 0.85rem;
    }

    .files-table th {
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

    .files-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #F3F4F6;
        color: #6B7280;
        vertical-align: middle;
        font-size: 0.8rem;
        white-space: nowrap;
    }

    .files-table tbody tr:hover {
        background: #F9FAFB;
    }

    .files-table tbody tr:last-child td {
        border-bottom: none;
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

    .file-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 0.8rem;
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
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .file-type.pdf {
        background: #FEE2E2;
        color: #DC2626;
    }

    .file-type.excel {
        background: #D1FAE5;
        color: #059669;
    }

    .file-type.word {
        background: #DBEAFE;
        color: #2563EB;
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
    }

    /* Custom Scrollbar for webkit browsers */
    .files-modal-body::-webkit-scrollbar,
    .files-table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .files-modal-body::-webkit-scrollbar-track,
    .files-table-container::-webkit-scrollbar-track {
        background: #F3F4F6;
        border-radius: 4px;
    }

    .files-modal-body::-webkit-scrollbar-thumb,
    .files-table-container::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 4px;
    }

    .files-modal-body::-webkit-scrollbar-thumb:hover,
    .files-table-container::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }

    /* Responsive Design for Files Modal */
    @media (max-width: 768px) {
        .files-modal {
            margin: 1rem;
            max-width: calc(100vw - 2rem);
        }

        .files-modal-header,
        .files-modal-body,
        .files-modal-footer {
            padding: 1rem;
        }

        .files-section-header {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .files-table {
            font-size: 0.75rem;
        }

        .files-table th,
        .files-table td {
            padding: 0.75rem 1rem;
        }
    }

    @media (max-width: 480px) {
        .files-modal {
            max-height: 95vh;
        }

        .file-name-cell {
            min-width: 150px;
        }

        .file-name-cell span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 120px;
        }
    }
</style>
@endpush
