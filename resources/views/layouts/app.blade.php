<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PBC Checklist') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.0/cdn.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1E293B 0%, #334155 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: relative;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo i {
            font-size: 1.2rem;
            color: white;
        }

        .sidebar-title {
            font-weight: 700;
            font-size: 1.1rem;
            white-space: nowrap;
            opacity: 1;
            transition: opacity 0.3s ease;
            margin-left: 1rem;
        }

        .sidebar.collapsed .sidebar-title {
            opacity: 0;
            pointer-events: none;
        }

        /* IMPROVED TOGGLE BUTTON - ALWAYS VISIBLE */
        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-50%) scale(1.1);
        }

        /* When collapsed, make toggle more prominent */
        .sidebar.collapsed .sidebar-toggle {
            background: rgba(59, 130, 246, 0.9);
            border-color: #3B82F6;
            right: 18px; /* Center it better when collapsed */
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .sidebar.collapsed .sidebar-toggle:hover {
            background: #3B82F6;
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        /* Toggle icon rotation animation */
        .sidebar-toggle i {
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #94A3B8;
            letter-spacing: 0.5px;
            white-space: nowrap;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .nav-section-title {
            opacity: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: #E2E8F0;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            background: rgba(59, 130, 246, 0.2);
            color: #60A5FA;
            border-right: 3px solid #3B82F6;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #3B82F6;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .nav-text {
            font-weight: 500;
            white-space: nowrap;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
        }

        .nav-badge {
            background: #EF4444;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 600;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .nav-badge {
            opacity: 0;
        }

        .user-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-details {
            flex: 1;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .user-details {
            opacity: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
        }

        .user-role {
            font-size: 0.75rem;
            color: #94A3B8;
        }

        .user-actions {
            background: none;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .user-actions:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* MAIN CONTENT STYLES */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            width: calc(100vw - 280px);
            max-width: calc(100vw - 280px);
            overflow-x: hidden;
        }

        .main-content.collapsed {
            margin-left: 80px;
            width: calc(100vw - 80px);
            max-width: calc(100vw - 80px);
        }

        .content-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: font-size 0.3s ease;
        }

        .page-subtitle {
            color: #6B7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-shrink: 0;
            min-width: 0;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 280px;
            min-width: 150px;
            max-width: 300px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #D1D5DB;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
        }

        /* NOTIFICATION BUTTON */
        .notification-btn {
            position: relative;
            background: none;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: #F3F4F6;
            color: #374151;
        }

        .notification-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 8px;
            height: 8px;
            background: #EF4444;
            border-radius: 50%;
        }

        /* PROFILE DROPDOWN */
        .profile-dropdown {
            position: relative;
        }

        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
        }

        .profile-trigger:hover {
            background: #F3F4F6;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 120px;
        }

        .profile-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1F2937;
            line-height: 1.2;
        }

        .profile-role {
            font-size: 0.75rem;
            color: #6B7280;
            line-height: 1.2;
        }

        .profile-chevron {
            color: #9CA3AF;
            transition: transform 0.3s ease;
        }

        .profile-trigger.open .profile-chevron {
            transform: rotate(180deg);
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #E5E7EB;
            min-width: 220px;
            z-index: 50;
            display: none;
        }

        .profile-menu.open {
            display: block;
        }

        .profile-menu-header {
            padding: 1rem;
            border-bottom: 1px solid #F3F4F6;
        }

        .profile-menu-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1F2937;
            margin-bottom: 0.25rem;
        }

        .profile-menu-email {
            font-size: 0.8rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
        }

        .profile-menu-role {
            font-size: 0.75rem;
            color: #9CA3AF;
            background: #F3F4F6;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }

        .profile-menu-body {
            padding: 0.5rem 0;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .profile-menu-item:hover {
            background: #F9FAFB;
            color: #1F2937;
        }

        .profile-menu-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6B7280;
        }

        .profile-menu-item:hover .profile-menu-icon {
            color: #3B82F6;
        }

        .profile-menu-separator {
            height: 1px;
            background: #F3F4F6;
            margin: 0.5rem 0;
        }

        .profile-menu-logout {
            color: #DC2626;
        }

        .profile-menu-logout:hover {
            background: #FEF2F2;
            color: #B91C1C;
        }

        .profile-menu-logout .profile-menu-icon {
            color: #DC2626;
        }

        .profile-menu-logout:hover .profile-menu-icon {
            color: #B91C1C;
        }

        .page-content {
            padding: 2rem;
            width: 100%;
            box-sizing: border-box;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100vw;
                max-width: 100vw;
            }

            .main-content.collapsed {
                margin-left: 0;
                width: 100vw;
                max-width: 100vw;
            }

            .search-input {
                width: 150px;
                min-width: 120px;
            }

            .profile-info {
                display: none;
            }

            .header-content {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .header-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 1400px) {
            .search-input {
                width: 220px;
            }
        }

        @media (max-width: 1200px) {
            .search-input {
                width: 200px;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 1024px) {
            .search-input {
                width: 180px;
            }

            .header-content {
                gap: 1rem;
            }

            .page-title {
                font-size: 1.4rem;
            }
        }

        /* Specific adjustments for when sidebar is expanded */
        @media (max-width: 1600px) {
            .main-content:not(.collapsed) .search-input {
                width: 200px;
            }
        }

        @media (max-width: 1400px) {
            .main-content:not(.collapsed) .search-input {
                width: 180px;
            }
        }

        @media (max-width: 1200px) {
            .main-content:not(.collapsed) .search-input {
                width: 160px;
            }

            .main-content:not(.collapsed) .page-title {
                font-size: 1.4rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="dashboard-container" x-data="pbcApp()">
        <!-- SIDEBAR -->
        @include('layouts.sidebar')

        <!-- MAIN CONTENT -->
        <div class="main-content" :class="{ 'collapsed': sidebarCollapsed }">
            <!-- HEADER -->
            <div class="content-header">
                <div class="header-content">
                    <div>
                        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                        <p class="page-subtitle">@yield('page-subtitle', 'Welcome back, ' . auth()->user()->name)</p>
                    </div>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" placeholder="Search anything..." x-model="searchQuery" @keyup.enter="performSearch()">
                        </div>

                        <!-- Notification Button -->
                        <button class="notification-btn" @click="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            @if(auth()->user()->unreadNotifications && auth()->user()->unreadNotifications->count() > 0)
                                <div class="notification-badge"></div>
                            @endif
                        </button>

                        <!-- Profile Dropdown -->
                        <div class="profile-dropdown">
                            <button class="profile-trigger" onclick="toggleProfileMenu()">
                                <div class="profile-avatar">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <div class="profile-info">
                                    <div class="profile-name">{{ Str::limit(auth()->user()->name, 15) }}</div>
                                    <div class="profile-role">{{ ucwords(str_replace('_', ' ', auth()->user()->role)) }}</div>
                                </div>
                                <i class="fas fa-chevron-down profile-chevron"></i>
                            </button>

                            <div class="profile-menu" id="profileMenu">
                                <!-- Profile Header -->
                                <div class="profile-menu-header">
                                    <div class="profile-menu-name">{{ auth()->user()->name }}</div>
                                    <div class="profile-menu-email">{{ auth()->user()->email }}</div>
                                    <div class="profile-menu-role">{{ ucwords(str_replace('_', ' ', auth()->user()->role)) }}</div>
                                </div>

                                <!-- Menu Items -->
                                <div class="profile-menu-body">
                                    <a href="#" class="profile-menu-item">
                                        <div class="profile-menu-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span>My Profile</span>
                                    </a>

                                    <a href="#" class="profile-menu-item">
                                        <div class="profile-menu-icon">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <span>Account Settings</span>
                                    </a>

                                    <a href="#" class="profile-menu-item">
                                        <div class="profile-menu-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <span>Activity Log</span>
                                    </a>

                                    <a href="#" class="profile-menu-item">
                                        <div class="profile-menu-icon">
                                            <i class="fas fa-question-circle"></i>
                                        </div>
                                        <span>Help & Support</span>
                                    </a>

                                    <div class="profile-menu-separator"></div>

                                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="profile-menu-item profile-menu-logout" style="width: 100%; border: none; background: none; text-align: left; cursor: pointer;">
                                            <div class="profile-menu-icon">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </div>
                                            <span>Sign Out</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mx-8 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mx-8 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- PAGE CONTENT -->
            <div class="page-content">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        function pbcApp() {
            return {
                sidebarCollapsed: false,
                showUserMenu: false,
                searchQuery: '',
                user: @json(auth()->user()),
                permissions: @json(auth()->user()->permissions ? auth()->user()->permissions->pluck('permission') : []),

                init() {
                    // Initialize any global app functionality
                    this.setupKeyboardShortcuts();
                    this.adjustLayoutDynamically();
                    console.log('PBC App initialized');
                },

                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    // Store preference in localStorage
                    localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);

                    // Adjust layout immediately after toggle
                    this.$nextTick(() => {
                        this.adjustLayoutDynamically();
                    });
                },

                adjustLayoutDynamically() {
                    const searchInput = document.querySelector('.search-input');
                    const pageTitle = document.querySelector('.page-title');
                    const headerActions = document.querySelector('.header-actions');

                    if (!searchInput || !pageTitle || !headerActions) return;

                    const viewportWidth = window.innerWidth;

                    // Calculate available space based on sidebar state
                    const sidebarWidth = this.sidebarCollapsed ? 80 : 280;
                    const availableWidth = viewportWidth - sidebarWidth;

                    // Adjust search input width based on available space
                    if (availableWidth < 900) {
                        searchInput.style.width = '150px';
                    } else if (availableWidth < 1100) {
                        searchInput.style.width = '180px';
                    } else if (availableWidth < 1300) {
                        searchInput.style.width = '220px';
                    } else {
                        searchInput.style.width = '280px';
                    }

                    // Adjust title font size based on available space
                    if (availableWidth < 800) {
                        pageTitle.style.fontSize = '1.4rem';
                    } else if (availableWidth < 1000) {
                        pageTitle.style.fontSize = '1.5rem';
                    } else {
                        pageTitle.style.fontSize = '1.75rem';
                    }

                    console.log(`Layout adjusted: Sidebar ${this.sidebarCollapsed ? 'collapsed' : 'expanded'}, Available width: ${availableWidth}px`);
                },

                toggleUserMenu() {
                    this.showUserMenu = !this.showUserMenu;
                    console.log('User menu toggled:', this.showUserMenu);
                },

                hasPermission(permission) {
                    return this.permissions.includes(permission);
                },

                performSearch() {
                    if (this.searchQuery.trim()) {
                        // Implement global search functionality
                        console.log('Searching for:', this.searchQuery);
                        // You can redirect to a search results page or implement live search
                    }
                },

                toggleNotifications() {
                    // Implement notification panel toggle
                    console.log('Toggle notifications');
                },

                setupKeyboardShortcuts() {
                    // Setup global keyboard shortcuts
                    document.addEventListener('keydown', (e) => {
                        // Ctrl/Cmd + K for search
                        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                            e.preventDefault();
                            document.querySelector('.search-input').focus();
                        }

                        // Ctrl/Cmd + B for sidebar toggle
                        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                            e.preventDefault();
                            this.toggleSidebar();
                        }
                    });

                    // Listen for window resize
                    window.addEventListener('resize', () => {
                        this.adjustLayoutDynamically();
                    });
                },

                async logout() {
                    try {
                        const response = await fetch('/api/v1/auth/logout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (response.ok) {
                            window.location.href = '/login';
                        }
                    } catch (error) {
                        console.error('Logout error:', error);
                        // Fallback to form submission
                        document.querySelector('form[action*="logout"]').submit();
                    }
                }
            }
        }

        // Simple dropdown toggle function
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            const trigger = document.querySelector('.profile-trigger');

            if (menu.classList.contains('open')) {
                menu.classList.remove('open');
                trigger.classList.remove('open');
            } else {
                menu.classList.add('open');
                trigger.classList.add('open');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.profile-dropdown');
            const menu = document.getElementById('profileMenu');

            if (dropdown && !dropdown.contains(event.target)) {
                menu.classList.remove('open');
                document.querySelector('.profile-trigger').classList.remove('open');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Restore sidebar state
            const sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (sidebarCollapsed) {
                const appElement = document.querySelector('[x-data*="pbcApp"]');
                if (appElement && appElement.__x) {
                    appElement.__x.$data.sidebarCollapsed = true;
                    // Adjust layout after restoring state
                    setTimeout(() => {
                        appElement.__x.$data.adjustLayoutDynamically();
                    }, 100);
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
