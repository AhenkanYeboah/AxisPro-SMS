@php
    $activeSchool = $currentSchool ?? (app()->bound('currentSchool') ? app('currentSchool') : null);
    $homeUrl = url('/school-home');
    $platformUrl = url('/');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ $activeSchool ? $homeUrl : $platformUrl }}">
            {{ $activeSchool->name ?? 'AxisPro SMS' }}
        </a>
        <div class="navbar-nav">
            @if($activeSchool)
                <a class="nav-link" href="{{ $homeUrl }}">School Home</a>
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>
            @endif
            <a class="nav-link" href="{{ $platformUrl }}">Platform Home</a>
        </div>
    </div>
</nav>
