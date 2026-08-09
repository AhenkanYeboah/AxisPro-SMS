@extends('layouts.dashboard')

@section('title', 'Virtual Classes')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Virtual Classes')
@section('welcome-message', $student->first_name . ' ' . $student->last_name)

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}"><i class="nav-icon">📊</i> Report Card</a>
    <a href="{{ route('student.fees') }}"><i class="nav-icon">💳</i> My Fees</a>
    <a href="{{ route('student.virtual-classes') }}" class="active"><i class="nav-icon">🎥</i> Virtual Classes</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('content')
    <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Join live from anywhere — perfect if you're traveling or learning remotely. Classes open here as soon as your teacher schedules them.</p>

    @forelse ($classes as $vc)
        <div class="card card-padded" style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div>
                    <strong>{{ $vc->title }}</strong>
                    @if ($vc->subject)
                        <span style="font-size:11px; color:var(--muted);">— {{ $vc->subject->name }}</span>
                    @endif
                    <br>
                    <span style="font-size:12px; color:var(--muted);">{{ $vc->scheduled_start->format('D, d M Y · g:i A') }}</span>
                </div>
                <div>
                    @if ($vc->isLive())
                        <span style="font-size:11px; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:10px; font-weight:600;">🔴 Live Now</span>
                    @elseif ($vc->isPast())
                        <span style="font-size:11px; background:#f1f5f9; color:var(--muted); padding:2px 8px; border-radius:10px;">Ended</span>
                    @else
                        <span style="font-size:11px; background:var(--green-light,#eef7ee); color:var(--green-deep); padding:2px 8px; border-radius:10px;">Upcoming</span>
                    @endif
                </div>
            </div>

            @if (! $vc->isPast())
                <a href="{{ route('student.virtual-classes.join', $vc) }}" target="_blank" rel="noopener noreferrer" style="display:inline-block; margin-top:10px; font-size:13px; padding:6px 16px; border-radius:6px; background:var(--green-deep); color:white; text-decoration:none;">
                    {{ $vc->isLive() ? 'Join Now →' : 'Join Early →' }}
                </a>
            @endif
        </div>
    @empty
        <div class="card card-padded">
            <p style="color:var(--muted); font-size:13px;">No virtual classes scheduled right now.</p>
        </div>
    @endforelse
@endsection
