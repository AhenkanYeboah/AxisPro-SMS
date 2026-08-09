@extends('layouts.dashboard')

@section('title', $notice->title . ' — Notices')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Notices')
@section('welcome-message', $notice->title)

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}" class="active"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
    <a href="{{ route('admin.settings') }}"><i class="nav-icon">🎨</i> Settings</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">🔑 <strong>{{ auth('admin')->user()->username }}</strong></span>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    <p style="margin-top:-8px; margin-bottom:20px;">
        <a href="{{ route('admin.notices.index') }}" style="font-size:13px; color:var(--muted);">← Back to Notices</a>
    </p>

    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">{{ $notice->title }}</h3>
        <p style="white-space:pre-line;">{{ $notice->body }}</p>
        <p style="font-size:12px; color:var(--muted); margin-bottom:0;">
            Sent by {{ $notice->sentBy->full_name ?? 'unknown' }} on {{ $notice->created_at->format('d M Y, g:ia') }}
            · Channel: {{ ucfirst($notice->channel) }}
            · Audience: {{ $notice->audience === 'class' ? $notice->class : ucfirst($notice->audience) }}
        </p>
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">Delivery status ({{ $notice->recipients->count() }} recipient(s))</h3>
        <table>
            <thead>
                <tr><th>Student</th><th>Email</th><th>SMS</th><th>Error</th></tr>
            </thead>
            <tbody>
                @forelse ($notice->recipients as $recipient)
                    <tr>
                        <td>{{ $recipient->student->fullName() }}</td>
                        <td>
                            @if ($recipient->email_status === 'sent') <span style="color:#2E7D32;">Sent</span>
                            @elseif ($recipient->email_status === 'failed') <span style="color:#B23B3B;">Failed</span>
                            @elseif ($recipient->email_status === 'skipped') <span style="color:var(--muted);">Skipped</span>
                            @else <span style="color:#B8860B;">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if ($recipient->sms_status === 'sent') <span style="color:#2E7D32;">Sent</span>
                            @elseif ($recipient->sms_status === 'failed') <span style="color:#B23B3B;">Failed</span>
                            @elseif ($recipient->sms_status === 'skipped') <span style="color:var(--muted);">Skipped</span>
                            @else <span style="color:#B8860B;">Pending</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--muted);">{{ $recipient->error_message ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center; color:var(--muted);">No recipients resolved for this notice yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
