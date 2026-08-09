@extends('layouts.dashboard')

@section('title', 'Invites')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Invites')
@section('welcome-message', 'Teacher Invites')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}" class="active"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
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
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Generate a new teacher invite</h3>
        <p style="font-size:13px; color:var(--muted);">
            Each code is single-use and lets one teacher sign up. Lock it to an email
            address so only the intended person can redeem it, or leave it open and
            hand it out directly.
        </p>
        <form method="POST" action="{{ route('admin.invites.store') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label>Email (optional — locks the code to this address)</label>
                <input type="email" name="email" placeholder="e.g., teacher@example.com">
            </div>
            <div class="form-group" style="margin:0;">
                <label>Expires in (days, optional)</label>
                <input type="number" name="expires_in_days" min="1" max="365" placeholder="e.g., 7">
            </div>
            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Generate</button>
        </form>
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">All invites</h3>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Locked To</th>
                    <th>Created By</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invites as $invite)
                    <tr>
                        <td><code>{{ $invite->code }}</code></td>
                        <td>{{ ucfirst($invite->type) }}</td>
                        <td>{{ $invite->email ?? '—' }}</td>
                        <td>{{ $invite->creator->full_name ?? '—' }}</td>
                        <td>{{ $invite->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                        <td>
                            @if ($invite->used_at)
                                <span style="color:var(--muted);">
                                    Used {{ $invite->used_at->format('M j, Y') }}
                                    by {{ $invite->usedByAdmin->full_name ?? $invite->usedByTeacher->full_name ?? 'unknown' }}
                                </span>
                            @elseif ($invite->expires_at && $invite->expires_at->isPast())
                                <span style="color:#B23B3B;">Expired</span>
                            @else
                                <span style="color:#2E7D32;">Active</span>
                            @endif
                        </td>
                        <td>
                            @if (!$invite->used_at)
                                <form method="POST" action="{{ route('admin.invites.destroy', $invite) }}" onsubmit="return confirm('Revoke this invite code?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="auth-btn" style="padding:4px 10px; font-size:11px;">Revoke</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--muted);">No invites generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
