@extends('layouts.dashboard')

@section('title', 'Student Dashboard')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Welcome')
@section('welcome-message', $student->first_name . ' ' . $student->last_name . ' 👋')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}" class="active"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}"><i class="nav-icon">📊</i> Report Card</a>
    <a href="{{ route('student.fees') }}"><i class="nav-icon">💳</i> My Fees</a>
    <a href="{{ route('student.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('topbar-right')
    <span class="user-greeting">Status: <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span>
@endsection

@section('content')
    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="font-family:'Cormorant Garamond',serif; font-size:20px; color:var(--green-deep); margin-bottom:12px;">📬 Notifications</h3>
        @foreach ($notifications as $notif)
            <div class="notification-item">
                <div class="date">{{ \Illuminate\Support\Carbon::parse($notif['date'])->format('M d, Y') }}</div>
                <div class="title">{{ $notif['title'] }}</div>
                <div style="font-size:13px; color:var(--muted);">{{ $notif['desc'] }}</div>
                @if (!empty($notif['action_url']))
                    <a href="{{ $notif['action_url'] }}" class="btn-gold" style="display:inline-block; margin-top:10px; padding:8px 18px; font-size:12px;">{{ $notif['action_label'] }}</a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="student-dash-grid">
        <div class="card">
            <div class="card-header">👤 Personal Information</div>
            <div class="card-body">
                <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">{{ $student->fullName() }}</span></div>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value">{{ $student->email }}</span></div>
                <div class="info-row"><span class="info-label">Date of Birth</span><span class="info-value">{{ $student->date_of_birth?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Gender</span><span class="info-value">{{ $student->gender ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Phone</span><span class="info-value">{{ $student->phone ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Address</span><span class="info-value">{!! nl2br(e($student->address ?? '—')) !!}</span></div>
                <div class="info-row"><span class="info-label">Class</span><span class="info-value">{{ $student->class ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">🏫 School Activities</div>
            <div class="card-body">
                @forelse ($activities as $activity)
                    <div class="activity-item">
                        <div>
                            <strong>{{ $activity->title }}</strong>
                            <div style="font-size:12px; color:var(--muted);">{{ $activity->description }}</div>
                            <div style="font-size:11px; color:var(--gold);">📅 {{ $activity->activity_date->format('M d, Y') }}</div>
                        </div>
                        <span style="font-size:11px; background:var(--gold-light); padding:2px 10px; border-radius:12px;">{{ $activity->category ?? 'General' }}</span>
                    </div>
                @empty
                    <p style="color:var(--muted);">No activities posted yet. Check back later.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
