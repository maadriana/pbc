<div class="sidebar" :class="{ 'collapsed': sidebarCollapsed }">
    <div class="sidebar-header">
        <div class="sidebar-title">PBC Checklist</div>
        <button class="sidebar-toggle" @click="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
<!-- MAIN SECTION - All users can access -->
<div class="nav-section">
    <div class="nav-section-title">Main</div>
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
        <span class="nav-text">Dashboard</span>
    </a>
    <a href="{{ route('progress.index') }}" class="nav-item {{ request()->routeIs('progress.*') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
        <span class="nav-text">Progress Tracker</span>
    </a>
</div>

        <!-- MANAGEMENT SECTION - Based on specific permissions -->
        @if(auth()->user()->hasPermission('view_user') ||
            auth()->user()->hasPermission('view_client') ||
            auth()->user()->hasPermission('view_project') ||
            auth()->user()->hasPermission('view_pbc_request'))
        <div class="nav-section">
            <div class="nav-section-title">Management</div>

            {{-- User Management - Only those with view_user permission (System Admin only) --}}
            @if(auth()->user()->hasPermission('view_user'))
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span class="nav-text">User Management</span>
            </a>
            @endif

            {{-- Client Management - Those with view_client permission (System Admin, Engagement Partner, Manager) --}}
            @if(auth()->user()->hasPermission('view_client'))
            <a href="{{ route('clients.index') }}" class="nav-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-building"></i></div>
                <span class="nav-text">Client Management</span>
            </a>
            @endif

            {{-- Project Management - Those with view_project permission --}}
            @if(auth()->user()->hasPermission('view_project'))
            <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-project-diagram"></i></div>
                <span class="nav-text">Project Management</span>
            </a>
            @endif

            {{-- PBC Requests - Those with view_pbc_request permission (All except guests in practice) --}}
            @if(auth()->user()->hasPermission('view_pbc_request'))
            <a href="{{ route('pbc-requests.index') }}" class="nav-item {{ request()->routeIs('pbc-requests.*') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                <span class="nav-text">PBC Requests</span>
                {{-- Static badge for UI viewing purposes --}}
                <span class="nav-badge">1</span>
            </a>
            @endif
            </a>
            @endif
        </div>


        <!-- DOCUMENTS SECTION - All users have upload access -->
        @if(auth()->user()->hasPermission('upload_document') || auth()->user()->hasPermission('view_document'))
        <div class="nav-section">
            <div class="nav-section-title">Documents</div>

            {{-- Document Archive - Show for all users (different access levels) --}}
            @if(auth()->user()->hasPermission('view_document'))
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-archive"></i></div>
                <span class="nav-text">Document Archive</span>
            </a>
            @endif
        </div>
        @endif

        <!-- COMMUNICATION SECTION - All users can access messages -->
        @if(auth()->user()->hasPermission('view_messages') || auth()->user()->hasPermission('receive_notifications'))
        <div class="nav-section">
            <div class="nav-section-title">Communication</div>

            {{-- Messages --}}
            <a href="{{ route('messages') }}" class="nav-item {{ request()->routeIs('messages') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-comments"></i></div>
                <span class="nav-text">Messages</span>
                {{-- We'll add unread count later once messages are flowing --}}
            </a>

            {{-- Reminders - Show for users who can send reminders --}}
            @if(auth()->user()->hasPermission('send_reminder'))
            @elseif(auth()->user()->hasPermission('receive_notifications'))
            {{-- Guests can see reminders but can't send them --}}
            <a href="#" class="nav-item">
                <div class="nav-icon"><i class="fas fa-bell"></i></div>
                <span class="nav-text">My Reminders</span>
            </a>
            @endif
        </div>
        @endif

        <!-- SYSTEM SECTION - Only those with manage_settings permission -->
        @if(auth()->user()->hasPermission('manage_settings'))
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <a href="{{ route('settings') }}" class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                <div class="nav-icon"><i class="fas fa-cog"></i></div>
                <span class="nav-text">Settings</span>
            </a>
        </div>
        @endif
    </nav>
</div>
