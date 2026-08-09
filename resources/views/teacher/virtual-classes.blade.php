@extends('layouts.dashboard')

@section('title', 'Virtual Classes')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Virtual Classes')
@section('welcome-message', 'Class: ' . ($classLevel?->displayName() ?? 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}"><i class="nav-icon">📊</i> Report Cards</a>
    <a href="{{ route('teacher.research-assistant') }}"><i class="nav-icon">🔎</i> Research Assistant</a>
    <a href="{{ route('teacher.virtual-classes') }}" class="active"><i class="nav-icon">🎥</i> Virtual Classes</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">👤 <strong>{{ $teacher->teacher_id }}</strong></span>
    <form method="POST" action="{{ route('teacher.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    @if ($noClassAssigned)
        <div class="card card-padded">
            <p style="color:var(--muted);">You don't have a class assigned. Please contact the admin.</p>
        </div>
    @else
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">🎥 Schedule a Virtual Class — {{ $classLevel->displayName() }}</h4>

            <form method="POST" action="{{ route('teacher.virtual-classes.store') }}" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                @csrf
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Science Revision — The Water Cycle" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject (optional)</label>
                    <select name="subject_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">— General —</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Duration (minutes) *</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 40) }}" min="10" max="180" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Date & Time *</label>
                    <input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start') }}" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Platform *</label>
                    <select name="platform" id="platform-select" onchange="document.getElementById('external-url-field').style.display = this.value === 'external_link' ? 'block' : 'none';" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="jitsi_auto" {{ old('platform', 'jitsi_auto') == 'jitsi_auto' ? 'selected' : '' }}>Quick Meeting (Jitsi — no account needed)</option>
                        @if ($zoomAvailable)
                            <option value="zoom_api" {{ old('platform') == 'zoom_api' ? 'selected' : '' }}>Zoom (auto-created)</option>
                        @endif
                        <option value="external_link" {{ old('platform') == 'external_link' ? 'selected' : '' }}>I already have a link (Zoom, Google Meet, etc.)</option>
                    </select>
                </div>
                <div id="external-url-field" style="grid-column:1 / -1; display:{{ old('platform') === 'external_link' ? 'block' : 'none' }};">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Meeting Link</label>
                    <input type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://meet.google.com/..." style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <button type="submit" class="auth-btn" style="width:100%;">Schedule Class</button>
                </div>
            </form>
            @unless ($zoomAvailable)
                <p style="font-size:11px; color:var(--muted); margin-top:10px;">💡 Zoom auto-creation isn't set up on this platform yet — Quick Meeting works with no setup, or paste a link you already have.</p>
            @endunless
        </div>

        <div class="card card-padded">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">Your Scheduled Classes</h4>

            @forelse ($classes as $vc)
                <div style="border:1.5px solid var(--border); border-radius:8px; padding:14px; margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                        <div>
                            <strong>{{ $vc->title }}</strong>
                            @if ($vc->subject)
                                <span style="font-size:11px; color:var(--muted);">— {{ $vc->subject->name }}</span>
                            @endif
                            <br>
                            <span style="font-size:12px; color:var(--muted);">{{ $vc->scheduled_start->format('D, d M Y · g:i A') }} ({{ $vc->scheduled_start->diffInMinutes($vc->scheduled_end) }} min)</span>
                        </div>
                        <div style="text-align:right;">
                            @if ($vc->status === 'cancelled')
                                <span style="font-size:11px; background:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:10px;">Cancelled</span>
                            @elseif ($vc->isLive())
                                <span style="font-size:11px; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:10px; font-weight:600;">🔴 Live Now</span>
                            @elseif ($vc->isPast())
                                <span style="font-size:11px; background:#f1f5f9; color:var(--muted); padding:2px 8px; border-radius:10px;">Ended</span>
                            @else
                                <span style="font-size:11px; background:var(--green-light,#eef7ee); color:var(--green-deep); padding:2px 8px; border-radius:10px;">Upcoming</span>
                            @endif
                        </div>
                    </div>

                    @if ($vc->status === 'scheduled')
                        <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
                            <a href="{{ $vc->join_url }}" target="_blank" rel="noopener noreferrer" style="font-size:12px; padding:5px 12px; border:1px solid var(--green-deep); border-radius:6px; background:var(--green-deep); color:white; text-decoration:none;">Start / Join →</a>
                            @if (! $vc->isPast())
                                <form method="POST" action="{{ route('teacher.virtual-classes.cancel', $vc) }}" onsubmit="return confirm('Cancel this class?');">
                                    @csrf
                                    <button type="submit" style="font-size:11px; padding:5px 12px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Cancel</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p style="color:var(--muted); font-size:13px;">No virtual classes scheduled yet.</p>
            @endforelse
        </div>
    @endif
@endsection
