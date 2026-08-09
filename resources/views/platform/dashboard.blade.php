@extends('layouts.dashboard')

@section('title', 'Platform Dashboard — AxisPro School Management System')
@section('sidebar-sub', 'Platform Admin')
@section('page-label', 'All Schools')
@section('welcome-message', 'Welcome, ' . auth('platform')->user()->name . ' 👋')

@section('nav-links')
    <a href="{{ route('platform.dashboard') }}" class="active"><i class="nav-icon">▤</i> Schools</a>
@endsection

@section('topbar-right')
    <form method="POST" action="{{ route('platform.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif

    {{-- STATS --}}
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon total"><div class="stat-info"><div class="stat-number">{{ $stats['total_schools'] }}</div><div class="stat-label">Total Schools</div></div></div></div>
        <div class="stat-card"><div class="stat-icon pending"><div class="stat-info"><div class="stat-number">{{ $stats['trial_schools'] }}</div><div class="stat-label">On Trial</div></div></div></div>
        <div class="stat-card"><div class="stat-icon admitted"><div class="stat-info"><div class="stat-number">{{ $stats['active_schools'] }}</div><div class="stat-label">Active / Paid</div></div></div></div>
        <div class="stat-card"><div class="stat-icon female"><div class="stat-info"><div class="stat-number">{{ $stats['suspended_schools'] }}</div><div class="stat-label">Suspended</div></div></div></div>
    </div>

    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto; margin-top:24px;">
        <table style="min-width:1100px;">
            <thead>
                <tr>
                    <th>#</th><th>School</th><th>Subdomain</th><th>Plan</th><th>Status</th>
                    <th>Trial Ends</th><th>Subscription Ends</th><th>Admins</th><th>Teachers</th><th>Students</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schools as $i => $school)
                    <tr>
                        <td style="color:var(--muted); font-size:12px;">{{ $i + 1 }}</td>
                        <td><a href="{{ route('platform.schools.show', $school) }}"><strong>{{ $school->name }}</strong></a></td>
                        <td style="color:var(--muted); font-size:13px;">{{ $school->subdomain }}</td>
                        <td>{{ $school->plan ? ucfirst($school->plan) : '—' }}</td>
                        <td>
                            @if ($school->displayStatus() === 'active')
                                <span class="status-badge status-active">Active</span>
                            @elseif ($school->displayStatus() === 'trial')
                                <span class="status-badge status-pending">Trial</span>
                            @elseif ($school->displayStatus() === 'expired')
                                <span class="status-badge status-declined" title="Subscription period has ended - the school is currently locked out even though it wasn't manually suspended.">Expired</span>
                            @else
                                <span class="status-badge status-declined">Suspended</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--muted);">{{ $school->trial_ends_at?->format('d M Y') ?? '—' }}</td>
                        <td style="font-size:12px; color:var(--muted);">{{ $school->subscription_ends_at?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $school->admins_count }}</td>
                        <td>{{ $school->teachers_count }}</td>
                        <td>{{ $school->students_count }}</td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                @if ($school->status === 'suspended')
                                    <form method="POST" action="{{ route('platform.schools.reactivate', $school) }}">
                                        @csrf
                                        <button type="submit" class="btn-gold" style="padding:6px 12px; font-size:11px;">Reactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.schools.suspend', $school) }}" onsubmit="return confirm('Suspend {{ $school->name }}? Their staff/students will be locked out immediately.');">
                                        @csrf
                                        <button type="submit" class="auth-btn auth-btn-logout" style="padding:6px 12px; font-size:11px;">Suspend</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align:center; color:var(--muted); padding:24px;">No schools yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
