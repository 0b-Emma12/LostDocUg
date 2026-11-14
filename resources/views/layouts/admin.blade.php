<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d293f">
    <meta name="description" content="LostDocUg Admin & Operations Portal - Secure document matching and recovery surveillance.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Operations | LostDocUg')</title>

    {{-- Typography: Outfit --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell" x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true', userDropdown: false }">
    <div class="admin-wrapper" :class="{ 'is-collapsed': sidebarCollapsed }">
        {{-- Mobile Backdrop --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="admin-backdrop"
            aria-hidden="true"
        ></div>

        {{-- Left Sidebar --}}
        <aside class="admin-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="admin-sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                    <span class="admin-brand-icon">LD</span>
                    <div class="admin-brand-text">
                        <strong>LostDocUg</strong>
                        <span class="admin-brand-sub">Ops Center</span>
                    </div>
                </a>
                <button
                    type="button"
                    class="admin-collapse-btn"
                    @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('admin_sidebar_collapsed', sidebarCollapsed)"
                    :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-expanded="!sidebarCollapsed"
                >
                    <svg class="admin-collapse-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
                <button
                    type="button"
                    class="admin-close-btn"
                    @click="sidebarOpen = false"
                    aria-label="Close sidebar"
                    title="Close sidebar"
                >
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="admin-sidebar-status">
                <div class="status-indicator">
                    <span class="status-dot-pulse"></span>
                    <span>Node: Uganda Central</span>
                </div>
                <span class="role-badge">{{ auth()->user()->role->value }}</span>
            </div>

            <nav class="admin-sidebar-nav" aria-label="Admin Navigation">
                <div class="nav-group-label">OVERVIEW</div>
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                    title="Dashboard"
                >
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-group-label">RECOVERY PIPELINE</div>
                <a
                    href="{{ route('admin.matches.index') }}"
                    class="admin-nav-item {{ request()->routeIs('admin.matches.*') ? 'is-active' : '' }}"
                    title="Match Queue"
                >
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Match Queue</span>
                    @php
                        $pendingCount = \App\Models\MatchCandidate::query()->whereIn('status', ['pending', 'under_review'])->count();
                    @endphp
                    @if ($pendingCount > 0)
                        <span class="nav-badge">{{ $pendingCount }}</span>
                    @endif
                </a>

                <a
                    href="{{ route('admin.dashboard') }}#claims"
                    class="admin-nav-item"
                    title="Claims Queue"
                >
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Claims Queue</span>
                    @php
                        $claimsCount = \App\Models\Claim::query()->where('status', 'pending')->count();
                    @endphp
                    @if ($claimsCount > 0)
                        <span class="nav-badge alert">{{ $claimsCount }}</span>
                    @endif
                </a>

                <div class="nav-group-label">SYSTEM & SECURITY</div>
                <a
                    href="{{ route('admin.dashboard') }}#audit"
                    class="admin-nav-item"
                    title="Audit Trail"
                >
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                    <span>Audit Trail</span>
                </a>

                <div class="nav-group-label">EXTERNAL</div>
                <a href="{{ route('dashboard') }}" class="admin-nav-item" title="Citizen Reports">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Citizen Reports</span>
                </a>
                <a href="{{ route('home') }}" class="admin-nav-item" target="_blank" title="Public Website">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>Public Website</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-profile">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="user-meta">
                        <strong class="user-name">{{ auth()->user()->name }}</strong>
                        <span class="user-email">{{ auth()->user()->email }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="admin-logout-form">
                    @csrf
                    <button type="submit" class="admin-logout-btn" title="Sign out of operations">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Shell --}}
        <div class="admin-main-wrapper">
            {{-- Topbar --}}
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button
                        type="button"
                        class="admin-menu-toggle"
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="Toggle navigation"
                    >
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="topbar-breadcrumb">
                        <span>LostDocUg</span>
                        <span class="bc-sep">/</span>
                        <span class="bc-current">@yield('page_title', 'Operations')</span>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="topbar-chip time-chip">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Kampala (EAT): {{ now()->setTimezone('Africa/Kampala')->format('H:i') }}</span>
                    </div>

                    <div class="topbar-chip status-chip">
                        <span class="dot-live"></span>
                        <span>Matching Engine Online</span>
                    </div>

                    <a href="{{ route('dashboard') }}" class="button button-secondary topbar-btn">
                        <span>Citizen Portal</span>
                    </a>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="admin-flash-banner success">
                    <div class="flash-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="admin-flash-banner error">
                    <div class="flash-inner">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- Content Slot --}}
            <div class="admin-content-area">
                @yield('content')
            </div>

            {{-- Admin Footer --}}
            <footer class="admin-footer">
                <div class="admin-footer-inner">
                    <p>LostDocUg Operations & Surveillance Console · Uganda Civil Registry Security Standard</p>
                    <p>Tamper-Evident Ledger · Append-Only Audit</p>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
