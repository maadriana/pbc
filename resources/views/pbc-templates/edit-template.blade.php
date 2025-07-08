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
                        <select class="info-select" x-model="clientData.client">
                            <option value="">Select Client...</option>
                            <option value="xyz-limited">XYZ Limited</option>
                            <option value="abc-corporation">ABC Corporation</option>
                            <option value="def-industries">DEF Industries Inc.</option>
                            <option value="ghi-holdings">GHI Holdings Ltd.</option>
                            <option value="jkl-enterprises">JKL Enterprises</option>
                            <option value="mno-company">MNO Company</option>
                            <option value="pqr-solutions">PQR Solutions Inc.</option>
                            <option value="stu-group">STU Group</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <label>Audit Period:</label>
                        <input type="date" class="info-input" x-model="clientData.auditPeriod">
                    </div>
                    <div class="info-row">
                        <label>Contact Person:</label>
                        <select class="info-select" x-model="clientData.contactPerson">
                            <option value="">Select Contact Person...</option>
                            <option value="james-martinez">James Martinez</option>
                            <option value="sarah-johnson">Sarah Johnson</option>
                            <option value="robert-chen">Robert Chen</option>
                            <option value="lisa-rodriguez">Lisa Rodriguez</option>
                            <option value="michael-brown">Michael Brown</option>
                            <option value="jennifer-davis">Jennifer Davis</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <input type="email" class="info-input" x-model="clientData.email" placeholder="Enter email address">
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-row">
                        <label>Engagement Partner:</label>
                        <select class="info-select" x-model="clientData.engagementPartner">
                            <option value="">Select Partner...</option>
                            <option value="maria-garcia">Maria Garcia</option>
                            <option value="james-martinez">James Martinez</option>
                            <option value="sarah-johnson">Sarah Johnson</option>
                            <option value="robert-chen">Robert Chen</option>
                            <option value="lisa-rodriguez">Lisa Rodriguez</option>
                            <option value="michael-brown">Michael Brown</option>
                            <option value="jennifer-davis">Jennifer Davis</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <label>Engagement Manager:</label>
                        <select class="info-select" x-model="clientData.engagementManager">
                            <option value="">Select Manager...</option>
                            <option value="carlos-reyes">Carlos Reyes</option>
                            <option value="anna-thompson">Anna Thompson</option>
                            <option value="david-wilson">David Wilson</option>
                            <option value="michelle-lopez">Michelle Lopez</option>
                            <option value="kevin-taylor">Kevin Taylor</option>
                            <option value="amanda-clark">Amanda Clark</option>
                            <option value="ryan-hall">Ryan Hall</option>
                            <option value="natalie-white">Natalie White</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <label>Document Date:</label>
                        <input type="date" class="info-input" x-model="clientData.documentDate">
                    </div>
                    <div class="info-row">
                        <label>Percentage of Completion:</label>
                        <input type="text" class="info-input" x-model="clientData.completionPercentage" placeholder="0%" readonly>
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
                            <th class="actions-col">Actions</th>
                            <th class="remarks-col">Remarks</th>
                        </tr>
                    </thead>
                    <tbody x-data="checklistItems()">
                        <!-- Section Header -->
                        <tr class="section-header">
                            <td colspan="9">
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
                                            <textarea class="particulars-input"
                                                :value="item.description"
                                                @input="updateItem(index, 'description', $event.target.value)"
                                                placeholder="Enter requirement description..."
                                                rows="2"></textarea>
                                        </div>
                                        <button class="btn-delete-item" @click="deleteItem(index)" title="Delete Item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="date-cell">
                                    <input type="date" class="date-input"
                                        :value="item.dateRequested"
                                        @input="updateItem(index, 'dateRequested', $event.target.value)">
                                </td>
                                <td class="assigned-cell">
                                    <select class="assigned-select"
                                        :value="item.assignedTo"
                                        @change="updateItem(index, 'assignedTo', $event.target.value)">
                                        <option value="">Select Person...</option>
                                        <option value="maria-garcia">Maria Garcia</option>
                                        <option value="james-martinez">James Martinez</option>
                                        <option value="sarah-johnson">Sarah Johnson</option>
                                        <option value="robert-chen">Robert Chen</option>
                                        <option value="lisa-rodriguez">Lisa Rodriguez</option>
                                        <option value="michael-brown">Michael Brown</option>
                                        <option value="jennifer-davis">Jennifer Davis</option>
                                        <option value="carlos-reyes">Carlos Reyes</option>
                                        <option value="anna-thompson">Anna Thompson</option>
                                        <option value="david-wilson">David Wilson</option>
                                        <option value="michelle-lopez">Michelle Lopez</option>
                                        <option value="kevin-taylor">Kevin Taylor</option>
                                        <option value="amanda-clark">Amanda Clark</option>
                                        <option value="ryan-hall">Ryan Hall</option>
                                        <option value="natalie-white">Natalie White</option>
                                    </select>
                                </td>
                                <td class="due-date-cell">
                                    <input type="date" class="due-date-input"
                                        :value="item.dueDate"
                                        @input="updateItem(index, 'dueDate', $event.target.value)">
                                </td>
                                <td class="requested-cell">
                                    <select class="requested-select"
                                        :value="item.requestedBy"
                                        @change="updateItem(index, 'requestedBy', $event.target.value)">
                                        <option value="">Select Person...</option>
                                        <option value="carlos-reyes">Carlos Reyes</option>
                                        <option value="anna-thompson">Anna Thompson</option>
                                        <option value="david-wilson">David Wilson</option>
                                        <option value="michelle-lopez">Michelle Lopez</option>
                                        <option value="kevin-taylor">Kevin Taylor</option>
                                        <option value="amanda-clark">Amanda Clark</option>
                                        <option value="ryan-hall">Ryan Hall</option>
                                        <option value="natalie-white">Natalie White</option>
                                        <option value="maria-garcia">Maria Garcia</option>
                                        <option value="james-martinez">James Martinez</option>
                                        <option value="sarah-johnson">Sarah Johnson</option>
                                        <option value="robert-chen">Robert Chen</option>
                                        <option value="lisa-rodriguez">Lisa Rodriguez</option>
                                        <option value="michael-brown">Michael Brown</option>
                                        <option value="jennifer-davis">Jennifer Davis</option>
                                    </select>
                                </td>
                                <td class="status-cell">
                                    <select class="status-select" :value="item.status" @change="updateItem(index, 'status', $event.target.value)">
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="overdue">Overdue</option>
                                    </select>
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
                                                    <button class="btn-file-download" @click="downloadFile(file.name)" title="Download File">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="no-files" x-show="!item.hasFiles">
                                        <span class="no-files-text">No files attached</span>
                                    </div>
                                </td>
                                <td class="actions-cell">
                                    <div x-show="item.hasFiles">
                                        <div class="file-actions-list">
                                            <template x-for="(file, fileIndex) in item.files" :key="file.name + '_actions'">
                                                <div class="file-actions-item">
                                                    <div class="file-actions-header">
                                                        <span class="file-actions-name" x-text="file.name.substring(0, 15) + (file.name.length > 15 ? '...' : '')"></span>
                                                    </div>
                                                    <div class="action-buttons">
                                                        <button class="action-btn accept" @click="acceptFile(index, fileIndex)" title="Accept File" :class="{ 'accepted': file.status === 'accepted' }">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="action-btn reject" @click="rejectFile(index, fileIndex)" title="Reject File" :class="{ 'rejected': file.status === 'rejected' }">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="no-actions" x-show="!item.hasFiles">
                                        <span class="no-actions-text">-</span>
                                    </div>
                                </td>
                                <td class="remarks-cell">
                                    <div x-show="item.hasFiles">
                                        <div class="file-remarks-list">
                                            <template x-for="(file, fileIndex) in item.files" :key="file.name + '_remarks'">
                                                <div class="file-remarks-item">
                                                    <div class="file-remarks-header">
                                                        <span class="file-remarks-name" x-text="file.name.substring(0, 20) + (file.name.length > 20 ? '...' : '')"></span>
                                                    </div>
                                                    <select class="remarks-select" :value="file.remarks" @change="updateFileRemarks(index, fileIndex, 'remarks', $event.target.value)" x-show="file.remarks !== 'others'">
                                                        <option value="">Select reason...</option>
                                                        <option value="documents_complete">Documents complete</option>
                                                        <option value="minor_issues">Minor issues</option>
                                                        <option value="missing_pages">Missing pages</option>
                                                        <option value="unclear_documents">Unclear documents</option>
                                                        <option value="outdated_version">Outdated version</option>
                                                        <option value="wrong_format">Wrong format</option>
                                                        <option value="others">Others</option>
                                                    </select>
                                                    <textarea class="remarks-textarea" :value="file.customRemarks" @input="updateFileRemarks(index, fileIndex, 'customRemarks', $event.target.value)" x-show="file.remarks === 'others'" placeholder="Specify concern..." rows="2"></textarea>
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

                        <!-- Add Item Row -->
                        <tr class="add-item-row">
                            <td colspan="9">
                                <button class="btn-add-item" @click="addItem()" title="Add New Item">
                                    <i class="fas fa-plus"></i>
                                    Add New Item
                                </button>
                            </td>
                        </tr>

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
            <button class="btn btn-primary btn-lg" @click="saveTemplate()">
                <i class="fas fa-paper-plane"></i>
                Submit Changes
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
        transition: all 0.3s ease;
    }

    .info-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .info-input[readonly] {
        background: #F9FAFB;
        color: #6B7280;
    }

    .info-select {
        flex: 1;
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 0.9rem;
        background-color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .info-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
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
        min-width: 1500px;
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
    .files-col { width: 16%; }
    .actions-col { width: 8%; }
    .remarks-col { width: 8%; }

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
        margin-top: 0.5rem;
        min-width: 20px;
    }

    .particulars-content {
        flex: 1;
    }

    .particulars-input {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.85rem;
        line-height: 1.4;
        resize: vertical;
        min-height: 50px;
        font-family: inherit;
    }

    .particulars-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .btn-delete-item {
        width: 24px;
        height: 24px;
        border: none;
        border-radius: 4px;
        background: #FEE2E2;
        color: #991B1B;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        margin-top: 0.25rem;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .btn-delete-item:hover {
        background: #FECACA;
    }

    /* Input Controls */
    .date-input, .due-date-input {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .date-input:focus, .due-date-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .assigned-select, .requested-select {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.85rem;
        background-color: white;
        cursor: pointer;
    }

    .assigned-select:focus, .requested-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
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
        justify-content: space-between;
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

    .btn-file-download {
        width: 28px;
        height: 28px;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.3s ease;
        background: #FFFFFF;
        color: #374151;
        flex-shrink: 0;
    }

    .btn-file-download:hover {
        background: #F3F4F6;
        border-color: #9CA3AF;
        color: #1F2937;
    }

    /* File Actions Section */
    .file-actions-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .file-actions-item {
        padding: 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
    }

    .file-actions-header {
        margin-bottom: 0.5rem;
    }

    .file-actions-name {
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        display: block;
    }

    /* File Remarks Section */
    .file-remarks-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .file-remarks-item {
        padding: 0.5rem;
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 4px;
    }

    .file-remarks-header {
        margin-bottom: 0.5rem;
    }

    .file-remarks-name {
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        display: block;
    }

    .no-files, .no-actions, .no-remarks {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9CA3AF;
        font-style: italic;
        font-size: 0.8rem;
        padding: 1rem;
    }

    /* Select Controls */
    .status-select, .remarks-select {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.8rem;
        background: white;
        line-height: 1.4;
        min-width: 110px;
    }

    .status-select:focus, .remarks-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .remarks-textarea {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.8rem;
        margin-top: 0.5rem;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
        line-height: 1.4;
    }

    .remarks-textarea:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .remarks-textarea::placeholder {
        color: #9CA3AF;
        font-style: italic;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.25rem;
        justify-content: center;
    }

    .action-btn {
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.3s ease;
    }

    .action-btn.accept {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .action-btn.accept:hover {
        background: #A7F3D0;
    }

    .action-btn.accept.accepted {
        background: #10B981;
        color: white;
        border-color: #059669;
    }

    .action-btn.reject {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
    }

    .action-btn.reject:hover {
        background: #FECACA;
    }

    .action-btn.reject.rejected {
        background: #EF4444;
        color: white;
        border-color: #DC2626;
    }

    /* Add Item Button */
    .btn-add-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 1rem;
        background: #F3F4F6;
        border: 2px dashed #D1D5DB;
        border-radius: 8px;
        color: #6B7280;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-add-item:hover {
        background: #E5E7EB;
        border-color: #9CA3AF;
        color: #374151;
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
        justify-content: space-between;
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

    .btn-primary {
        background: linear-gradient(135deg, #3B82F6, #1D4ED8);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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
            // Client Data
            clientData: {
                client: 'xyz-limited',
                auditPeriod: '2024-12-31',
                contactPerson: 'james-martinez',
                email: 'john.smith@xyzlimited.com',
                engagementPartner: 'maria-garcia',
                engagementManager: 'carlos-reyes',
                documentDate: '2025-07-07',
                completionPercentage: '23%'
            },

            // Initialize
            init() {
                console.log('🚀 PBC Template Management Init (Editable)');
            },

            // Actions
            goBack() {
                console.log('Going back to PBC requests...');
                window.location.href = '/pbc-requests';
            },

            saveTemplate() {
                console.log('Saving template...');
                console.log('Client Data:', this.clientData);
                this.showAlert('Template saved successfully!', 'success');
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
                    dateRequested: '2025-01-02',
                    assignedTo: 'maria-garcia',
                    dueDate: '2025-02-15',
                    requestedBy: 'carlos-reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'Articles_of_Incorporation.pdf', type: 'pdf', size: '2.4 MB', remarks: 'documents_complete', customRemarks: '' },
                        { name: 'Company_Bylaws.pdf', type: 'pdf', size: '1.8 MB', remarks: 'documents_complete', customRemarks: '' }
                    ]
                },
                {
                    description: 'BIR Certificate of Registration',
                    dateRequested: '2025-01-02',
                    assignedTo: 'james-martinez',
                    dueDate: '2025-02-10',
                    requestedBy: 'carlos-reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'BIR_Certificate_2024.pdf', type: 'pdf', size: '856 KB', remarks: 'documents_complete', customRemarks: '' }
                    ]
                },
                {
                    description: 'Latest General Information Sheet filed with the SEC',
                    dateRequested: '2025-01-02',
                    assignedTo: 'anna-thompson',
                    dueDate: '2025-02-20',
                    requestedBy: 'carlos-reyes',
                    status: 'pending',
                    hasFiles: false,
                    files: []
                },
                {
                    description: 'Stock transfer book',
                    dateRequested: '2025-01-02',
                    assignedTo: 'david-wilson',
                    dueDate: '2025-02-12',
                    requestedBy: 'carlos-reyes',
                    status: 'completed',
                    hasFiles: true,
                    files: [
                        { name: 'Stock_Transfer_Book_2024.xlsx', type: 'excel', size: '1.2 MB', remarks: 'minor_issues', customRemarks: '' },
                        { name: 'Stock_Certificates_Register.pdf', type: 'pdf', size: '3.1 MB', remarks: 'documents_complete', customRemarks: '' }
                    ]
                },
                {
                    description: 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period from January 1, 2024 to date.',
                    dateRequested: '2025-01-02',
                    assignedTo: 'michelle-lopez',
                    dueDate: '2025-01-30',
                    requestedBy: 'carlos-reyes',
                    status: 'overdue',
                    hasFiles: false,
                    files: []
                }
            ],

            addItem() {
                const today = new Date().toISOString().split('T')[0];
                const dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + 30);
                const dueDateStr = dueDate.toISOString().split('T')[0];

                this.items.push({
                    description: '',
                    dateRequested: today,
                    assignedTo: '',
                    dueDate: dueDateStr,
                    requestedBy: 'carlos-reyes',
                    status: 'pending',
                    hasFiles: false,
                    files: []
                });
                console.log('New item added');
            },

            deleteItem(index) {
                if (confirm('Delete this item?')) {
                    this.items.splice(index, 1);
                    console.log(`Item ${index} deleted`);
                }
            },

            updateItem(index, field, value) {
                this.items[index][field] = value;
                console.log(`Item ${index} ${field} updated to:`, value);
            },

            updateFileRemarks(itemIndex, fileIndex, field, value) {
                this.items[itemIndex].files[fileIndex][field] = value;

                // If remarks changed to 'others', clear customRemarks for other values
                if (field === 'remarks' && value !== 'others') {
                    this.items[itemIndex].files[fileIndex].customRemarks = '';
                }

                console.log(`File ${fileIndex} in item ${itemIndex} ${field} updated to:`, value);
            },

            acceptFile(itemIndex, fileIndex) {
                this.items[itemIndex].files[fileIndex].status = 'accepted';
                console.log(`File ${fileIndex} in item ${itemIndex} accepted`);
                this.showAlert(`File accepted`, 'success');
            },

            rejectFile(itemIndex, fileIndex) {
                this.items[itemIndex].files[fileIndex].status = 'rejected';
                console.log(`File ${fileIndex} in item ${itemIndex} rejected`);
                this.showAlert(`File rejected`, 'warning');
            },

            viewFile(fileName) {
                console.log(`Viewing file: ${fileName}`);
                this.showAlert(`Opening ${fileName} for preview`, 'info');
            },

            downloadFile(fileName) {
                console.log(`Downloading file: ${fileName}`);
                this.showAlert(`Downloading ${fileName}`, 'success');
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
