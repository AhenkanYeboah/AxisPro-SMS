<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (($currentSchool ?? null)?->name ?? 'AxisPro School Management System'))</title>
    <link rel="icon" href="{{ asset('images/axispro-logo.webp') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            @if($currentSchool ?? false)
                @if($currentSchool->logo_path)
                    {{-- Use fallback route, with onerror to default logo if file was wiped on Render --}}
                    <img src="{{ route('storage.fallback', $currentSchool->logo_path) }}" 
                         alt="{{ $currentSchool->name }} logo" 
                         style="max-height:50px; max-width:180px; object-fit:contain;"
                         onerror="this.onerror=null; this.src='{{ asset('images/axispro-logo.webp') }}';">
                @else
                    <img src="{{ asset('images/axispro-logo.webp') }}" alt="{{ $currentSchool->name }} logo" style="max-height:50px;">
                @endif
                <div class="brand-name">{{ $currentSchool->name }}</div>
            @else
                {{-- Platform-admin panel: no tenant in context --}}
                <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro" style="max-height:50px;">
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
                @yield('topbar-right')
                {{-- Example logout that won't 419 anymore - put this in your nav if not already --}}
                @hasSection('topbar-right')
                @else
                    @auth('admin')
                        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm">Logout</button>
                        </form>
                    @endauth
                    @auth('platform')
                        <form method="POST" action="{{ route('platform.logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm">Logout</button>
                        </form>
                    @endauth
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="card card-padded" style="margin-bottom:20px; background:#F1EBFB; border-left:4px solid #5B3A9E;">
                {{ session('status') }}
            </div>
        @endif

        @if (session('success'))
            <div class="card card-padded" style="margin-bottom:20px; background:#ecfdf5; border-left:4px solid #10b981; color:#065f46;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="card card-padded" style="margin-bottom:20px; background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
