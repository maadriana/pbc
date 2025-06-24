<div class="sidebar" :class="{ 'collapsed': sidebarCollapsed }">
    <div class="sidebar-header">
        <div class="sidebar-title">PBC Checklist</div>
        <button class="sidebar-toggle" @click="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <!-- MAIN SECTION -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                <span class="nav-text">Progress Tracker</span>
            </a>
        </div>

<!-- MANAGEMENT SECTION -->
        @if(auth()->user()->hasPermission('view_user') || auth()->user()->hasPermission('view_client') || auth()->user()->hasPermission('view_project'))
        <div class="nav-section">
            <div class="nav-section-title">Management</div>

            @if(auth()->user()->hasPermission('view_user'))
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span class="nav-text">User Management</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_client'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-building"></i></div>
                <span class="nav-text">Client Management</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_project'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-project-diagram"></i></div>
                <span class="nav-text">Project Management</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_pbc_request'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                <span class="nav-text">PBC Requests</span>
                @php
                    $pendingCount = App\Models\PbcRequest::where('status', 'pending')->count();
                @endphp
                @if($pendingCount > 0)
                    <span class="nav-badge">{{ $pendingCount }}</span>
                @endif
            </a>
            @endif
        </div>
        @endif

        <!-- DOCUMENTS SECTION -->
        @if(auth()->user()->hasPermission('upload_document') || auth()->user()->hasPermission('approve_document'))
        <div class="nav-section">
            <div class="nav-section-title">Documents</div>
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <span class="nav-text">Upload Center</span>
            </a>

            @if(auth()->user()->hasPermission('approve_document'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                <span class="nav-text">Document Review</span>
                @php
                    $pendingDocs = App\Models\PbcDocument::where('status', 'pending')->count();
                @endphp
                @if($pendingDocs > 0)
                    <span class="nav-badge">{{ $pendingDocs }}</span>
                @endif
            </a>
            @endif

            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-archive"></i></div>
                <span class="nav-text">Document Archive</span>
            </a>
        </div>
        @endif

        <!-- COMMUNICATION SECTION -->
        <div class="nav-section">
            <div class="nav-section-title">Communication</div>
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-comments"></i></div>
                <span class="nav-text">Messages</span>
                @php
                    $unreadMessages = auth()->user()->unreadNotifications->count();
                @endphp
                @if($unreadMessages > 0)
                    <span class="nav-badge">{{ $unreadMessages }}</span>
                @endif
            </a>

            @if(auth()->user()->hasPermission('send_reminder'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-bell"></i></div>
                <span class="nav-text">Reminders</span>
            </a>
            @endif
        </div>

        <!-- REPORTS SECTION -->
        @if(auth()->user()->hasPermission('export_reports') || auth()->user()->hasPermission('view_audit_log'))
        <div class="nav-section">
            <div class="nav-section-title">Reports</div>
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
                <span class="nav-text">Analytics</span>
            </a>

            @if(auth()->user()->hasPermission('export_reports'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-file-export"></i></div>
                <span class="nav-text">Export Reports</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_audit_log'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-history"></i></div>
                <span class="nav-text">Audit Trail</span>
            </a>
            @endif
        </div>
        @endif

        <!-- SYSTEM SECTION -->
        @if(auth()->user()->hasPermission('manage_settings') || auth()->user()->hasPermission('manage_permissions'))
        <div class="nav-section">
            <div class="nav-section-title">System</div>

            @if(auth()->user()->hasPermission('manage_settings'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-cog"></i></div>
                <span class="nav-text">Settings</span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_permissions'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-shield-alt"></i></div>
                <span class="nav-text">Permissions</span>
            </a>
            @endif
        </div>
        @endif
    </nav>
</div>
