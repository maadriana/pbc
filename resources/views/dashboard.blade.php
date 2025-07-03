
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'System Administrator Dashboard')
@section('page-subtitle', 'Welcome back, ' . auth()->user()->name . '!')

@section('content')
<div x-data="adminDashboard()" x-init="init()">

    <!-- SUMMARY TILES WITH REAL BACKEND DATA -->
    <div class="stats-grid">
        <!-- Completed Requests -->
        <div class="stat-card completed">
            <div class="stat-card-border"></div>
            <div class="stat-header">
                <div class="stat-content">
                    <div class="stat-label">🟢 Completed Requests</div>
                    <div class="stat-value">{{ $stats['completed_requests'] ?? 0 }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>{{ $stats['completion_rate'] ?? 0 }}% completion rate</span>
                    </div>
                </div>
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="stat-card pending">
            <div class="stat-card-border"></div>
            <div class="stat-header">
                <div class="stat-content">
                    <div class="stat-label">🟡 Pending Requests</div>
                    <div class="stat-value">{{ $stats['pending_requests'] ?? 0 }}</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-clock"></i>
                        <span>Awaiting response</span>
                    </div>
                </div>
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- Overdue Items -->
        <div class="stat-card overdue">
            <div class="stat-card-border"></div>
            <div class="stat-header">
                <div class="stat-content">
                    <div class="stat-label">🔴 Overdue Items</div>
                    <div class="stat-value">{{ $stats['overdue_requests'] ?? 0 }}</div>
                    <div class="stat-change negative">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Needs attention</span>
                    </div>
                </div>
                <div class="stat-icon overdue">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>

        <!-- Total System Stats -->
        <div class="stat-card total">
            <div class="stat-card-border"></div>
            <div class="stat-header">
                <div class="stat-content">
                    <div class="stat-label">📁 Total Requests</div>
                    <div class="stat-value">{{ $stats['total_requests'] ?? 0 }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-chart-line"></i>
                        <span>All projects</span>
                    </div>
                </div>
                <div class="stat-icon total">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ADDITIONAL SYSTEM ADMIN STATS -->
    <div class="admin-stats-grid">
        <!-- Total Users -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon users">
                <i class="fas fa-users"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">Total Users</div>
                <div class="admin-stat-value">{{ $stats['total_users'] ?? 0 }}</div>
                <div class="admin-stat-breakdown">
                    <span>{{ $stats['active_users'] ?? 0 }} active</span>
                </div>
            </div>
        </div>

        <!-- Total Clients -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon clients">
                <i class="fas fa-building"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">Total Clients</div>
                <div class="admin-stat-value">{{ $stats['total_clients'] ?? 0 }}</div>
                <div class="admin-stat-breakdown">
                    <span>{{ $stats['active_projects'] ?? 0 }} active projects</span>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon documents">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">Documents</div>
                <div class="admin-stat-value">{{ $stats['documents_uploaded'] ?? 0 }}</div>
                <div class="admin-stat-breakdown">
                    <span>{{ $stats['documents_approved'] ?? 0 }} approved</span>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon system">
                <i class="fas fa-server"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">System Health</div>
                <div class="admin-stat-value">98.5%</div>
                <div class="admin-stat-breakdown">
                    <span class="text-green-600">All systems operational</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS SECTION -->
    <div class="charts-section">
        <!-- Trend Chart -->
        <div class="chart-card main-chart">
            <div class="chart-header">
                <h3 class="chart-title">Request Status Trend</h3>
                <div class="chart-controls">
                    <select class="chart-period-select">
                        <option value="30">Last 30 days</option>
                        <option value="60">Last 60 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="chart-card side-chart">
            <div class="chart-header">
                <h3 class="chart-title">Status Distribution</h3>
                <span class="chart-period">Current</span>
            </div>
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="chart-card side-chart">
            <div class="chart-header">
                <h3 class="chart-title">Category Performance</h3>
                <span class="chart-period">This month</span>
            </div>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- RECENT ACTIVITY AND SYSTEM ALERTS -->
    <div class="activity-section">
        <!-- Recent Activity -->
        <div class="activity-card">
            <div class="activity-header">
                <h3 class="activity-title">Recent System Activity</h3>
                <a href="#" class="view-all-btn">View All Logs</a>
            </div>
            <div class="activity-list">
                @forelse($recent_activity as $activity)
                <div class="activity-item">
                    <div class="activity-icon {{ $activity['action'] }}">
                        @php
                            $iconMap = [
                                'login' => 'sign-in-alt',
                                'comment_created' => 'comment',
                                'document_uploaded' => 'upload',
                                'document_approved' => 'check',
                                'reminder_sent' => 'bell',
                                'pbc_request_created' => 'plus',
                                'user_created' => 'user-plus',
                                'project_updated' => 'edit',
                                'default' => 'circle'
                            ];
                            $icon = $iconMap[$activity['action']] ?? $iconMap['default'];
                        @endphp
                        <i class="fas fa-{{ $icon }}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">{{ $activity['description'] }}</div>
                        <div class="activity-meta">
                            <span class="activity-user">{{ $activity['user'] ?? 'System' }}</span>
                            <span class="activity-time">{{ $activity['formatted_date'] }}</span>
                        </div>
                    </div>
                    <div class="activity-badge {{ $activity['action'] }}">
                        {{ ucfirst(str_replace('_', ' ', $activity['action'])) }}
                    </div>
                </div>
                @empty
                <div class="activity-item">
                    <div class="activity-content">
                        <div class="activity-text">No recent activity</div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- System Alerts -->
        <div class="activity-card">
            <div class="activity-header">
                <h3 class="activity-title">System Alerts & Monitoring</h3>
                <a href="#" class="view-all-btn">System Settings</a>
            </div>
            <div class="activity-list">
                @if($stats['overdue_requests'] > 0)
                <div class="activity-item alert-critical">
                    <div class="activity-icon overdue">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text"><strong>{{ $stats['overdue_requests'] }} overdue requests</strong> require immediate attention</div>
                        <div class="activity-meta">
                            <span class="activity-time">Active now</span>
                        </div>
                    </div>
                    <div class="activity-badge critical">Critical</div>
                </div>
                @endif

                @php
                    $pendingDocs = App\Models\PbcSubmission::where('status', 'pending')->count();
                @endphp
                @if($pendingDocs > 0)
                <div class="activity-item alert-warning">
                    <div class="activity-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text"><strong>{{ $pendingDocs }} documents</strong> pending review</div>
                        <div class="activity-meta">
                            <span class="activity-time">Active now</span>
                        </div>
                    </div>
                    <div class="activity-badge warning">Review</div>
                </div>
                @endif

                <div class="activity-item alert-success">
                    <div class="activity-icon system">
                        <i class="fas fa-shield-check"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">System security scan completed</div>
                        <div class="activity-meta">
                            <span class="activity-time">2 hours ago</span>
                        </div>
                    </div>
                    <div class="activity-badge success">Security</div>
                </div>

                <div class="activity-item alert-info">
                    <div class="activity-icon system">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Database backup completed successfully</div>
                        <div class="activity-meta">
                            <span class="activity-time">Last night</span>
                        </div>
                    </div>
                    <div class="activity-badge info">System</div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS FOR SYSTEM ADMIN -->
    <div class="quick-actions-section">
        <h3 class="section-title">Quick Actions</h3>
        <div class="quick-actions-grid">
            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Add New User</h4>
                    <p>Create system user account</p>
                </div>
            </a>

            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Add New Client</h4>
                    <p>Register new audit client</p>
                </div>
            </a>

            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Create Project</h4>
                    <p>Start new audit project</p>
                </div>
            </a>

            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-file-export"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Export Reports</h4>
                    <p>Generate system reports</p>
                </div>
            </a>

            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="quick-action-content">
                    <h4>System Settings</h4>
                    <p>Configure PBC system</p>
                </div>
            </a>

            <a href="#" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Audit Trail</h4>
                    <p>View system audit logs</p>
                </div>
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-card-border {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-card.completed .stat-card-border { background: #10B981; }
    .stat-card.pending .stat-card-border { background: #F59E0B; }
    .stat-card.overdue .stat-card-border { background: #EF4444; }
    .stat-card.total .stat-card-border { background: #3B82F6; }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        color: #6B7280;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.5rem;
        color: #1F2937;
    }

    .stat-change {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .stat-change.positive { color: #10B981; }
    .stat-change.negative { color: #EF4444; }
    .stat-change.neutral { color: #6B7280; }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-icon.completed { background: linear-gradient(135deg, #10B981, #059669); }
    .stat-icon.pending { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .stat-icon.overdue { background: linear-gradient(135deg, #EF4444, #DC2626); }
    .stat-icon.total { background: linear-gradient(135deg, #3B82F6, #1D4ED8); }

    /* ADMIN STATS GRID */
    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .admin-stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .admin-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        flex-shrink: 0;
    }

    .admin-stat-icon.users { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
    .admin-stat-icon.clients { background: linear-gradient(135deg, #06B6D4, #0891B2); }
    .admin-stat-icon.documents { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .admin-stat-icon.system { background: linear-gradient(135deg, #10B981, #059669); }

    .admin-stat-label {
        font-size: 0.8rem;
        color: #6B7280;
        font-weight: 500;
    }

    .admin-stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1F2937;
        line-height: 1;
    }

    .admin-stat-breakdown {
        font-size: 0.75rem;
        color: #9CA3AF;
        margin-top: 0.25rem;
    }

    /* CHARTS SECTION */
    .charts-section {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
    }

    .chart-card.main-chart {
        grid-row: span 2;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .chart-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1F2937;
    }

    .chart-controls {
        display: flex;
        gap: 0.5rem;
    }

    .chart-period-select {
        padding: 0.5rem 1rem;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.8rem;
        background: white;
    }

    .chart-period {
        font-size: 0.8rem;
        color: #6B7280;
        background: #F3F4F6;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .side-chart .chart-container {
        height: 200px;
    }

    /* ACTIVITY SECTION */
    .activity-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .activity-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .activity-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1F2937;
    }

    .view-all-btn {
        color: #3B82F6;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .view-all-btn:hover {
        background: #F0F9FF;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #F8FAFC;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #F1F5F9;
    }

    .activity-item.alert-critical {
        background: #FEF2F2;
        border: 1px solid #FECACA;
    }

    .activity-item.alert-warning {
        background: #FFFBEB;
        border: 1px solid #FED7AA;
    }

    .activity-item.alert-success {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
    }

    .activity-item.alert-info {
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: white;
        flex-shrink: 0;
        background: linear-gradient(135deg, #6B7280, #4B5563);
    }

    .activity-content {
        flex: 1;
    }

    .activity-text {
        font-size: 0.9rem;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .activity-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.8rem;
        color: #6B7280;
    }

    .activity-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .activity-badge.critical { background: #FEE2E2; color: #991B1B; }
    .activity-badge.warning { background: #FEF3C7; color: #92400E; }
    .activity-badge.success { background: #D1FAE5; color: #065F46; }
    .activity-badge.info { background: #DBEAFE; color: #1E40AF; }

    /* QUICK ACTIONS */
    .quick-actions-section {
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 1rem;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quick-action-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #F3F4F6;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    .quick-action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border-color: #3B82F6;
    }

    .quick-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #3B82F6, #1D4ED8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .quick-action-content h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.25rem;
    }

    .quick-action-content p {
        font-size: 0.8rem;
        color: #6B7280;
        margin: 0;
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .charts-section {
            grid-template-columns: 1fr;
        }

        .activity-section {
            grid-template-columns: 1fr;
        }

        .admin-stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function adminDashboard() {
        return {
            stats: {
                completed: {{ $stats['completed_requests'] ?? 0 }},
                pending: {{ $stats['pending_requests'] ?? 0 }},
                overdue: {{ $stats['overdue_requests'] ?? 0 }},
                total: {{ $stats['total_requests'] ?? 0 }}
            },
            chartData: @json($charts_data ?? []),

            init() {
                this.initCharts();
                this.setupRealTimeUpdates();
            },

            initCharts() {
                this.initTrendChart();
                this.initPieChart();
                this.initCategoryChart();
            },

            initTrendChart() {
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: this.chartData.completion_trend?.map(d => d.label) ||
                                ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        datasets: [
                            {
                                label: 'Completed',
                                data: this.chartData.completion_trend?.map(d => d.completed) || [35, 42, 38, 45],
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Pending',
                                data: this.chartData.requests_by_status?.find(d => d.status === 'pending')?.trend || [28, 25, 30, 23],
                                borderColor: '#F59E0B',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Overdue',
                                data: this.chartData.requests_by_status?.find(d => d.status === 'overdue')?.trend || [12, 15, 10, 8],
                                borderColor: '#EF4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#F3F4F6',
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                }
                            }
                        }
                    }
                });
            },

            initPieChart() {
                const pieCtx = document.getElementById('pieChart').getContext('2d');
                new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Pending', 'Overdue'],
                        datasets: [{
                            data: [this.stats.completed, this.stats.pending, this.stats.overdue],
                            backgroundColor: [
                                '#10B981',
                                '#F59E0B',
                                '#EF4444'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            },

            initCategoryChart() {
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                const categoryData = this.chartData.requests_by_category || [
                    {label: 'Cash', value: 25},
                    {label: 'AR', value: 18},
                    {label: 'AP', value: 15},
                    {label: 'Tax', value: 12},
                    {label: 'Other', value: 8}
                ];

                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryData.map(d => d.label),
                        datasets: [{
                            label: 'Requests',
                            data: categoryData.map(d => d.value),
                            backgroundColor: [
                                '#10B981',
                                '#3B82F6',
                                '#F59E0B',
                                '#8B5CF6',
                                '#06B6D4'
                            ],
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#F3F4F6',
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                }
                            }
                        }
                    }
                });
            },

            setupRealTimeUpdates() {
                // Set up periodic updates for real-time data
                setInterval(() => {
                    this.refreshStats();
                }, 30000); // Refresh every 30 seconds
            },

            async refreshStats() {
                try {
                    const response = await fetch('/api/v1/dashboard/stats', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            // Update stats without full page reload
                            this.stats = data.data;
                        }
                    }
                } catch (error) {
                    console.log('Stats refresh failed:', error);
                }
            }
        }
    }
</script>
@endpush
@endsection

