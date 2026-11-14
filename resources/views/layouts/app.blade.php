<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d293f">
    <meta name="description" content="LostDocUg helps people report lost and found documents privately and review possible matches securely.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LostDocUg | Private Document Recovery')</title>

    {{-- Typography: Outfit --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-shell" x-data="{ mobileNav: false }">
    {{-- Sticky Header --}}
    <header class="ul-header">
        <div class="ul-header-inner">
            <div class="ul-header-left">
                <a class="ul-brand" href="{{ route('home') }}" aria-label="LostDocUg Home">
                    <span class="ul-brand-mark">LD</span>
                    <div class="ul-brand-info">
                        <strong class="ul-brand-name">LostDocUg</strong>
                        <span class="ul-brand-tagline">Private Document Recovery</span>
                    </div>
                </a>

                <nav class="ul-nav-links" aria-label="Primary Navigation">
                    <a href="{{ route('home') }}" class="ul-nav-item {{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('dashboard') }}" class="ul-nav-item {{ request()->routeIs('dashboard', 'directory.*') ? 'is-active' : '' }}">
                        My reports &amp; matches
                    </a>
                    <a href="{{ route('home') }}#how-it-works" class="ul-nav-item">How It Works</a>
                    <a href="{{ route('privacy') }}" class="ul-nav-item {{ request()->routeIs('privacy') ? 'is-active' : '' }}">Privacy First</a>
                </nav>
            </div>

            <div class="ul-header-right">
                <div class="ul-header-ctas">
                    <a href="{{ route('reports.create', ['kind' => 'lost']) }}" class="ul-btn-report lost" title="File a report for your lost identification card">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Report Lost ID</span>
                    </a>
                    <a href="{{ route('reports.create', ['kind' => 'found']) }}" class="ul-btn-report found" title="Safely submit an ID you have discovered">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>I Found an ID</span>
                    </a>
                </div>

                <div class="ul-auth-cluster">
                    @auth
                        @if(auth()->user()->canAccessAdmin())
                            <a class="ul-badge-portal" href="{{ route('admin.dashboard') }}">
                                <span class="dot"></span>
                                <span>Admin Portal</span>
                            </a>
                        @endif
                        <a class="ul-user-link" href="{{ route('dashboard') }}">
                            <span>My Filings</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button class="ul-logout-text-btn" type="submit">Sign out</button>
                        </form>
                    @else
                        <a class="ul-login-link" href="{{ route('login') }}">Sign in</a>
                        <a class="ul-register-btn" href="{{ route('register') }}">Create Account</a>
                    @endauth
                </div>

                <button
                    type="button"
                    class="ul-mobile-menu-btn"
                    @click="mobileNav = !mobileNav"
                    aria-label="Toggle navigation menu"
                >
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Dropdown Nav --}}
        <div x-show="mobileNav" x-cloak class="ul-mobile-dropdown">
            <nav class="ul-mobile-nav">
                <a href="{{ route('home') }}" class="ul-mobile-nav-item">Home</a>
                <a href="{{ route('dashboard') }}" class="ul-mobile-nav-item">My reports &amp; matches</a>
                <a href="{{ route('home') }}#how-it-works" class="ul-mobile-nav-item">How It Works</a>
                <a href="{{ route('reports.create', ['kind' => 'lost']) }}" class="ul-mobile-nav-item lost-highlight">+ Report Lost Document/ID</a>
                <a href="{{ route('reports.create', ['kind' => 'found']) }}" class="ul-mobile-nav-item found-highlight">✓ I Found a Document/ID</a>
                <hr class="ul-mobile-nav-divider">
                @auth
                    @if(auth()->user()->canAccessAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="ul-mobile-nav-item admin">Admin Operations</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="ul-mobile-nav-item">My Filings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ul-mobile-nav-item ul-mobile-nav-signout">
                            Sign Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="ul-mobile-nav-item">Sign in</a>
                    <a href="{{ route('register') }}" class="ul-mobile-nav-item">Create account</a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Main Content --}}
    @yield('content')

    {{-- Directory Footer --}}
    <footer class="ul-footer">
        <div class="ul-footer-main">
            <div class="ul-footer-col brand-col">
                <a class="ul-brand" href="{{ route('home') }}">
                    <span class="ul-brand-mark">LD</span>
                    <strong class="ul-brand-name">LostDocUg</strong>
                </a>
                <p class="ul-footer-about">
                    Uganda's official privacy-preserving identification document recovery network. Cross-referencing lost and found credentials securely.
                </p>
                <div class="ul-footer-badge">
                    <span class="shield-icon"><x-icon name="shield" class="shield-ic" /></span>
                    <span>PII Encrypted · Privacy-by-Design</span>
                </div>
            </div>

            <div class="ul-footer-col">
                <h4 class="ul-footer-heading">Private Recovery</h4>
                <ul class="ul-footer-list">
                    <li><a href="{{ route('dashboard') }}">My reports &amp; possible matches</a></li>
                    <li><a href="{{ route('reports.create', ['kind' => 'lost']) }}">Report Missing ID</a></li>
                    <li><a href="{{ route('reports.create', ['kind' => 'found']) }}">Turn In Found ID</a></li>
                    <li><a href="{{ route('privacy') }}">Privacy & Security Standards</a></li>
                    <li><a href="{{ route('home') }}#faq">Frequently Asked Questions</a></li>
                </ul>
            </div>

            <div class="ul-footer-col">
                <h4 class="ul-footer-heading">Uganda Hubs</h4>
                <p class="ul-footer-hub-text">Covering civil records across Kampala, Wakiso, Mukono, Jinja, Gulu, Mbarara, and all 135+ administrative districts.</p>
                <div class="ul-emergency-contact">
                    <small>Official Civil Helpline</small>
                    <strong>+256 700 000 000</strong>
                </div>
            </div>
        </div>

        <div class="ul-footer-bottom">
            <div class="ul-footer-bottom-inner">
                <p>© {{ date('Y') }} LostDocUg. All rights reserved.</p>

                <div class="ul-footer-bottom-links">
                    <a href="{{ route('privacy') }}">Data Protection Notice</a>
                    <span>·</span>
                    <a href="{{ route('home') }}#how-it-works">Recovery Guidelines</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
