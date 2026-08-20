<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', (($currentSchool ?? null)?->name ?? 'AxisPro School Management System'))</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

{{--
    This file replaces the sidebar/topbar markup that was copy-pasted at the top
    of every $page == '...' branch in the original file. @yield()/@section()
    is Blade's version of "fill in this slot" - each page only writes the
    parts that differ (nav links, page title, main content).

    Multi-tenant note: the school shown here is whichever tenant ResolveTenant
    resolved from the subdomain ($currentSchool, shared with every view). This
    layout no longer hardcodes any one school's name/crest - each tenant sees
    their own branding, falling back to a generic logo mark if they haven't
    uploaded one yet.
--}}

@php
    // Resolve active school context if $currentSchool is unpopulated
    $activeSchool = $currentSchool ?? (session()->has('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
    
    // Check if the current context matches Royal Countryside Academy or any active tenant
    $schoolHomeUrl = $activeSchool ? url('/school-home') : route('platform.home');
@endphp

<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            @if($currentSchool ?? false)
                @if($currentSchool->logo_path)
                    <img src="{{ Storage::disk('public')->url($currentSchool->logo_path) }}" alt="{{ $currentSchool->name }} logo" onerror="this.style.display='none'">
                @endif
                <div class="brand-name">{{ $currentSchool->name }}</div>
            @else
                {{-- Platform-admin panel: no tenant in context (exempted from ResolveTenant) --}}
                <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro">
                <div class="brand-name">AxisPro</div>
            @endif
            <div class="brand-sub">@yield('sidebar-sub', 'Portal')</div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-section-label">Navigation</p>
            @yield('nav-links')
        </nav>
        <div class="sidebar-footer">
            <p class="footer-signature">
                <a href="https://www.facebook.com/share/19E5UdnunZ/" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">
                    <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro" style="height:16px; width:16px; object-fit:contain; vertical-align:middle; margin-right:4px;">
                    Powered by <strong>AxisPro School Management System</strong>
                </a>
            </p>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">
                    <span>@yield('page-label', 'Dashboard')</span>
                    @yield('welcome-message')
                </div>
            </div>
            <div class="auth-links">
                {{-- School Home Link --}}
                <a href="{{ $schoolHomeUrl }}" class="btn-topbar" style="margin-right: 12px; text-decoration: none; font-weight: 500;">
                    🏠 School Home
                </a>
                
                @yield('topbar-right')
            </div>
        </div>

        @if (session('status'))
            <div class="card card-padded" style="margin-bottom:20px; background:#F1EBFB; border-left:4px solid #5B3A9E;">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
