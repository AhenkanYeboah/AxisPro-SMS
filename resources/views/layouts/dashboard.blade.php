<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
@php
    // 1. Resolve active school via shared variable, session, or authenticated user guard
    $activeSchool = $currentSchool ?? null;

    if (!$activeSchool && session()->has('active_school_id')) {
        $activeSchool = \App\Models\School::find(session('active_school_id'));
    }

    if (!$activeSchool) {
        foreach (['admin', 'teacher', 'student', 'web'] as $guard) {
            if (auth($guard)->check() && !empty(auth($guard)->user()->school_id)) {
                $activeSchool = \App\Models\School::find(auth($guard)->user()->school_id);
                break;
            }
        }
    }

    // 2. Safe URL generation to prevent RouteNotFoundException
    $homeUrl = Route::has('school.home') ? route('school.home') : url('/school-home');
    $platformUrl = Route::has('platform.home') ? route('platform.home') : url('/');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ $activeSchool ? $homeUrl : $platformUrl }}">
            {{ $activeSchool->name ?? 'AxisPro SMS' }}
        </a>
        <div class="navbar-nav">
            @if($activeSchool)
                <a class="nav-link" href="{{ $homeUrl }}">School Home</a>
            @endif
            <a class="nav-link" href="{{ $platformUrl }}">Platform Home</a>
        </div>
    </div>
</nav>

<main class="container">
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
