@extends('layouts.public')

@section('title', $school->name . ' — Admissions Portal')

@section('content')
{{--
    Every school after RCA renders through here rather than a hand-built
    page each - name/logo/tagline/contact/color all come from the `schools`
    row a school's own admin edits from Settings. If a school hasn't set a
    primary_color yet, this intentionally falls through to app.css's shared
    default (the same neutral purple used across the whole platform) rather
    than overriding it - only schools that have actually chosen a color see
    one applied.
--}}
<div class="landing-page" @if($school->primary_color) style="--green-deep:{{ $school->primary_color }};" @endif>
    <div class="landing-left">
        <div class="landing-left-content">
            @if ($school->logo_path)
                <img src="{{ Storage::disk('public')->url($school->logo_path) }}" alt="{{ $school->name }} logo">
            @else
                <div style="width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.12); display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:var(--white); margin:0 auto 20px;">
                    {{ strtoupper(substr($school->name, 0, 1)) }}
                </div>
            @endif
            <h1 style="font-size:30px;">{{ $school->name }}</h1>
        </div>
    </div>
    <div class="landing-right">
        <p class="eyebrow">Student Admission Portal</p>
        <h2>Welcome to {{ $school->name }}</h2>
        <p class="tagline">{{ $school->tagline ?? 'Complete your enrollment application online and track your admission status through your personalised dashboard.' }}</p>

        <ul class="feature-list">
            <li><span class="feat-dot"></span>Online admissions &amp; real-time status updates</li>
            <li><span class="feat-dot"></span>Digital attendance, assignments &amp; report cards</li>
            <li><span class="feat-dot"></span>Dedicated portals for admins, teachers &amp; students</li>
        </ul>

        <a href="{{ route('student.form') }}" class="portal-link">Begin Enrollment <span class="arrow">→</span></a>
        <p style="margin-top:12px; font-size:13px;">
            Already enrolled? <a href="{{ route('student.login') }}" style="color:var(--green-deep); font-weight:600;">Log in here</a>
        </p>
        <p style="margin-top:6px; font-size:13px;">
            <a href="{{ route('activities.index') }}" style="color:var(--green-deep); font-weight:600;">📋 View School Activities</a>
        </p>
        <p style="margin-top:6px; font-size:13px;">
            <a href="{{ route('admin.login') }}" style="color:var(--green-deep); font-weight:600;">🔐 Admin Login</a>
            &nbsp;·&nbsp;
            <a href="{{ route('teacher.login') }}" style="color:var(--green-deep); font-weight:600;">🔐 Teacher Login</a>
        </p>
        @if ($school->phone || $school->contact_email)
            <p class="landing-footer-note">
                @if ($school->phone) 📞 {{ $school->phone }} @endif
                @if ($school->phone && $school->contact_email) &nbsp;·&nbsp; @endif
                @if ($school->contact_email) {{ $school->contact_email }} @endif
            </p>
        @endif
        <p class="landing-footer-note" style="margin-top:16px; opacity:0.6;">
            Powered by <strong>AxisPro School Management System</strong>
        </p>
    </div>
</div>
@endsection
