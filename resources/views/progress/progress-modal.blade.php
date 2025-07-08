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
            <div class="progress-categories">
                <div class="category-tabs">
                    <button class="category-tab active" @click="switchTab('completed')" :class="{ 'active': activeTab === 'completed' }" x-data="{ activeTab: 'completed' }">
                        <i class="fas fa-check-circle"></i>
                        <span>Completed</span>
                        <span class="tab-count" x-text="completedItems.length"></span>
                    </button>
                    <button class="category-tab" @click="switchTab('pending')" :class="{ 'active': activeTab === 'pending' }">
                        <i class="fas fa-clock"></i>
                        <span>Pending</span>
                        <span class="tab-count" x-text="pendingItems.length"></span>
                    </button>
                    <button class="category-tab" @click="switchTab('overdue')" :class="{ 'active': activeTab === 'overdue' }">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Overdue</span>
                        <span class="tab-count" x-text="overdueItems.length"></span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Completed Items -->
                    <div class="tab-pane" x-show="activeTab === 'completed'" x-data="{ activeTab: 'completed' }">
                        <div class="items-list">
                            <template x-for="(item, index) in completedItems" :key="index">
                                <div class="progress-item completed">
                                    <div class="item-icon">
                                        <i class="fas fa-check-circle text-green-600"></i>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-title" x-text="item.title"></div>
                                        <div class="item-meta">
                                            <span class="item-date" x-text="'Completed: ' + item.completedDate"></span>
                                            <span class="item-assignee" x-text="'By: ' + item.assignee"></span>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <span class="status-badge status-completed">Completed</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pending Items -->
                    <div class="tab-pane" x-show="activeTab === 'pending'">
                        <div class="items-list">
                            <template x-for="(item, index) in pendingItems" :key="index">
                                <div class="progress-item pending">
                                    <div class="item-icon">
                                        <i class="fas fa-clock text-yellow-600"></i>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-title" x-text="item.title"></div>
                                        <div class="item-meta">
                                            <span class="item-date" x-text="'Due: ' + item.dueDate"></span>
                                            <span class="item-assignee" x-text="'Assigned: ' + item.assignee"></span>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <span class="status-badge status-pending">Pending</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Overdue Items -->
                    <div class="tab-pane" x-show="activeTab === 'overdue'">
                        <div class="items-list">
                            <template x-for="(item, index) in overdueItems" :key="index">
                                <div class="progress-item overdue">
                                    <div class="item-icon">
                                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-title" x-text="item.title"></div>
                                        <div class="item-meta">
                                            <span class="item-date" x-text="'Overdue: ' + item.overdueBy + ' days'"></span>
                                            <span class="item-assignee" x-text="'Assigned: ' + item.assignee"></span>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <span class="status-badge status-overdue">Overdue</span>
                                    </div>
                                </div>
                            </template>
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
        max-width: 900px;
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
        gap: 1.5rem;
        min-height: 0;
    }

    /* Project Summary */
    .project-summary {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 1.5rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .summary-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .summary-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .progress-fraction {
        font-size: 0.8rem;
        color: #6B7280;
        font-weight: 500;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
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
        max-height: 400px;
        overflow-y: auto;
    }

    .items-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .progress-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        background: #F9FAFB;
        transition: all 0.3s ease;
    }

    .progress-item:hover {
        background: #F3F4F6;
        border-color: #D1D5DB;
    }

    .item-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .progress-item.completed .item-icon {
        background: #D1FAE5;
    }

    .progress-item.pending .item-icon {
        background: #FEF3C7;
    }

    .progress-item.overdue .item-icon {
        background: #FEE2E2;
    }

    .item-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .item-title {
        font-weight: 600;
        color: #1F2937;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .item-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.8rem;
        color: #6B7280;
    }

    .item-status {
        flex-shrink: 0;
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
            grid-template-columns: 1fr;
        }

        .category-tabs {
            flex-direction: column;
        }

        .progress-modal-footer {
            flex-direction: column;
            gap: 1rem;
        }

        .item-meta {
            flex-direction: column;
            gap: 0.25rem;
        }
    }

    @media (max-width: 480px) {
        .progress-modal {
            max-height: 98vh;
        }

        .tab-content {
            max-height: 250px;
            padding: 1rem;
        }

        .progress-item {
            padding: 0.75rem;
        }

        .item-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
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

            switchTab(tab) {
                this.activeTab = tab;
                console.log('Switched to tab:', tab);
            },

            generateProgressData() {
                // Sample completed items
                this.completedItems = [
                    {
                        title: 'Latest Articles of Incorporation and By-laws',
                        completedDate: 'Jul 8, 2025',
                        assignee: 'Maria Garcia'
                    },
                    {
                        title: 'BIR Certificate of Registration',
                        completedDate: 'Jul 7, 2025',
                        assignee: 'James Martinez'
                    },
                    {
                        title: 'Bank statements for all accounts',
                        completedDate: 'Jul 6, 2025',
                        assignee: 'Kevin Taylor'
                    },
                    {
                        title: 'General ledger for all accounts',
                        completedDate: 'Jul 5, 2025',
                        assignee: 'Ryan Hall'
                    },
                    {
                        title: 'Trial balance as at December 31, 2024',
                        completedDate: 'Jul 4, 2025',
                        assignee: 'Amanda Clark'
                    }
                ];

                // Sample pending items
                this.pendingItems = [
                    {
                        title: 'Latest General Information Sheet filed with SEC',
                        dueDate: 'Jul 15, 2025',
                        assignee: 'Anna Thompson'
                    },
                    {
                        title: 'Stock transfer book',
                        dueDate: 'Jul 18, 2025',
                        assignee: 'David Wilson'
                    },
                    {
                        title: 'Fixed assets register',
                        dueDate: 'Jul 20, 2025',
                        assignee: 'Michelle Lopez'
                    }
                ];

                // Sample overdue items
                this.overdueItems = [
                    {
                        title: 'Minutes of meetings of stockholders and board',
                        overdueBy: 5,
                        assignee: 'Michelle Lopez'
                    },
                    {
                        title: 'Accounts receivable aging report',
                        overdueBy: 3,
                        assignee: 'Carlos Reyes'
                    }
                ];
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
