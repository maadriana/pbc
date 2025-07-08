@extends('layouts.app')

@section('title', 'Create PBC Request Template')
@section('page-title', 'PBC Request Management')
@section('page-subtitle', 'Manage document requests, track submissions, and monitor progress')

@section('content')
<div x-data="pbcCreateTemplateManagement()" x-init="init()">
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
                            <th class="days-col">Due Date</th>
                            <th class="notes-col">Notes</th>
                        </tr>
                    </thead>
                    <tbody x-data="checklistItems()">
                        <!-- Section Header -->
                        <tr class="section-header">
                            <td colspan="5">
                                <div class="section-header-content">
                                    <input type="text" class="section-input" x-model="sectionTitle" placeholder="Enter section title">
                                    <button class="btn-delete-section" @click="deleteSection()" title="Delete Section">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Checklist Items -->
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="checklist-item">
                                <td class="particulars-cell">
                                    <div class="particulars-row">
                                        <span class="item-number" x-text="(index + 1) + '.'"></span>
                                        <input type="text" class="particulars-input" :value="item.description" @input="updateItem(index, 'description', $event.target.value)" placeholder="Enter requirement description">
                                        <button class="btn-delete-item" @click="deleteItem(index)" title="Delete Item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="date-cell">
                                    <input type="date" class="date-input" :value="item.date" @input="updateItem(index, 'date', $event.target.value); updateDaysOutstanding(index)">
                                </td>
                                <td class="assigned-cell">
                                    <select class="assigned-select" :value="item.assignedTo" @change="updateItem(index, 'assignedTo', $event.target.value)">
                                        <option value="">Select Assignee...</option>
                                        <option value="carlos-reyes">Carlos Reyes</option>
                                        <option value="anna-thompson">Anna Thompson</option>
                                        <option value="david-wilson">David Wilson</option>
                                        <option value="michelle-lopez">Michelle Lopez</option>
                                        <option value="kevin-taylor">Kevin Taylor</option>
                                        <option value="amanda-clark">Amanda Clark</option>
                                        <option value="ryan-hall">Ryan Hall</option>
                                        <option value="natalie-white">Natalie White</option>
                                        <option value="james-martinez">James Martinez</option>
                                        <option value="sarah-johnson">Sarah Johnson</option>
                                        <option value="robert-chen">Robert Chen</option>
                                        <option value="lisa-rodriguez">Lisa Rodriguez</option>
                                    </select>
                                </td>
                                <td class="date-cell">
                                    <input type="date" class="date-input" :value="item.date" @input="updateItem(index, 'date', $event.target.value); updateDaysOutstanding(index)">
                                </td>
                                <td class="notes-cell">
                                    <textarea class="notes-input" :value="item.notes" @input="updateItem(index, 'notes', $event.target.value)" placeholder="Optional notes..." rows="2"></textarea>
                                </td>
                            </tr>
                        </template>

                        <!-- Add Item Row -->
                        <tr class="add-item-row">
                            <td colspan="5">
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
                Back
            </button>
            <button class="btn btn-primary btn-lg" @click="saveTemplate()">
                Done
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

    .code-input {
        background: #1F2937;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-size: 1.25rem;
        font-weight: 700;
        border: none;
        text-align: center;
        min-width: 120px;
    }

    .code-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .code-input::placeholder {
        color: #9CA3AF;
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
        gap: 1rem;
    }

    .info-row {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .info-row label {
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
    }

    .info-input, .info-select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .info-input:focus, .info-select:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .info-select {
        background-color: white;
        cursor: pointer;
    }

    /* Checklist Section */
    .checklist-section {
        padding: 2rem;
    }

    .table-container {
        border: 1px solid #E5E7EB;
        border-radius: 8px;
    }

    .checklist-table {
        width: 100%;
        border-collapse: collapse;
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

    /* Column widths - No horizontal scroll needed */
    .particulars-col { width: 35%; }
    .date-col { width: 15%; }
    .assigned-col { width: 20%; }
    .days-col { width: 15%; }
    .notes-col { width: 15%; }

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
        gap: 1rem;
        justify-content: space-between;
    }

    .section-input {
        flex: 1;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-weight: 600;
        color: #1E40AF;
        background: white;
        font-size: 0.9rem;
    }

    .section-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .btn-delete-section {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        background: #FEE2E2;
        color: #991B1B;
    }

    .btn-delete-section:hover {
        background: #FECACA;
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

    .particulars-input {
        flex: 1;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.85rem;
        line-height: 1.4;
        min-height: 60px;
        resize: vertical;
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
    }

    .btn-delete-item:hover {
        background: #FECACA;
    }

    /* Input Controls */
    .date-input, .days-input, .assigned-select, .notes-input {
        width: 100%;
        border: 1px solid #D1D5DB;
        border-radius: 4px;
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .date-input:focus, .days-input:focus, .assigned-select:focus, .notes-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .days-input {
        text-align: center;
    }

    .assigned-select {
        background: white;
        cursor: pointer;
    }

    .notes-input {
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
        line-height: 1.4;
    }

    .notes-input::placeholder {
        color: #9CA3AF;
        font-style: italic;
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
        justify-content: flex-end;
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
    function pbcCreateTemplateManagement() {
        return {
            // Client Data
            clientData: {
                client: '',
                auditPeriod: '',
                contactPerson: '',
                email: '',
                engagementPartner: '',
                engagementManager: '',
                documentDate: new Date().toISOString().split('T')[0]
            },

            // Template Data
            templateData: {
                code: 'AT-700',
                title: 'Audit Requirement Checklist'
            },

            // Initialize
            init() {
                console.log('🚀 PBC Create Template Management Init (UI Only)');
            },

            // Actions
            goBack() {
                console.log('Going back to create PBC request modal...');
                // Navigate back to PBC requests and trigger modal open
                window.location.href = '/pbc-requests?openModal=true';
            },

            saveTemplate() {
                console.log('Saving template...');
                console.log('Client Data:', this.clientData);
                console.log('Template Data:', this.templateData);
                this.showAlert('Template created successfully!', 'success');

                // Simulate save and redirect to modal
                setTimeout(() => {
                    this.goBack();
                }, 1500);
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
            sectionTitle: '1. Permanent File',

            items: [
                {
                    description: 'Latest Articles of Incorporation and By-laws',
                    date: '2025-02-01',
                    assignedTo: 'carlos-reyes',
                    date: '2025-02-01',
                    notes: ''
                },
                {
                    description: 'BIR Certificate of Registration',
                    date: '2025-02-01',
                    assignedTo: 'anna-thompson',
                    date: '2025-02-01',
                    notes: ''
                },
                {
                    description: 'Latest General Information Sheet filed with the SEC',
                    date: '2025-02-01',
                    assignedTo: 'david-wilson',
                    date: '2025-02-01',
                    notes: ''
                },
                {
                    description: 'Stock transfer book',
                    date: '2025-02-01',
                    assignedTo: 'michelle-lopez',
                    date: '2025-02-01',
                    notes: ''
                },
                {
                    description: 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period from January 1, (____) to date.',
                    date: '2025-02-01',
                    assignedTo: 'carlos-reyes',
                    date: '2025-02-01',
                    notes: ''
                }
            ],

            addItem() {
                const today = new Date().toISOString().split('T')[0];
                this.items.push({
                    description: '',
                    date: today,
                    assignedTo: '',
                    days: 0,
                    notes: ''
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

            updateDaysOutstanding(index) {
                const item = this.items[index];
                if (item.date) {
                    const requestDate = new Date(item.date);
                    const today = new Date();
                    const diffTime = today - requestDate;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    this.items[index].days = Math.max(0, diffDays);
                }
            },

            deleteSection() {
                if (confirm('Delete this section and all its items?')) {
                    console.log('Section deleted');
                    this.sectionTitle = '';
                    this.items = [];
                    // Create alert
                    const parentComponent = document.querySelector('[x-data*="pbcCreateTemplateManagement"]').__x;
                    if (parentComponent && parentComponent.$data.showAlert) {
                        parentComponent.$data.showAlert('Section deleted', 'warning');
                    }
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
