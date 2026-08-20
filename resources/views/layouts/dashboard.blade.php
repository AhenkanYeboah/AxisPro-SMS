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

@php
    // 1. Resolve active school via shared variable, session, or authenticated user models
    $activeSchool = $currentSchool ?? null;

    if (!$activeSchool && session()->has('active_school_id')) {
        $activeSchool = \App\Models\School::find(session('active_school_id'));
    }

    if (!$activeSchool) {
        foreach (['admin', 'teacher', 'student', 'web'] as $guard) {
            if (auth($guard)->check() && !empty(auth($guard)->user()->school_id)) {
                $activeSchool = \App\Models\School::find(auth($guard)->user()->school_id);
                if ($activeSchool) break;
            }
        }
    }

    // 2. Generate destination URL based on resolved context
    $schoolHomeUrl = $activeSchool ? url('/school-home') : route('platform.home');
@endphp

<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            @if($activeSchool)
                @if($activeSchool->logo_path)
                    <img src="{{ Storage::disk('public')->url($activeSchool->logo_path) }}" alt="{{ $activeSchool->name }} logo" onerror="this.style.display='none'">
                @endif
                <div class="brand-name">{{ $activeSchool->name }}</div>
            @else
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
