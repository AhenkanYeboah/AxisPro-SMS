@extends('layouts.dashboard')

@section('title', 'Teachers')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Teachers')
@section('welcome-message', 'Class assignments')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}" class="active"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
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
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif

    @if ($classLevels->isEmpty())
        <div class="message error">
            No classes have been set up yet - go to <a href="{{ route('admin.class-levels.index') }}">Classes</a> and add some before assigning teachers.
        </div>
    @endif

    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
        <table style="min-width:800px;">
            <thead>
                <tr><th>Teacher</th><th>ID</th><th>Class</th><th>Curriculum</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->full_name }}</td>
                        <td>{{ $teacher->teacher_id }}</td>
                        <td colspan="3" style="padding:0;">
                            <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" style="display:flex; align-items:center; gap:10px; padding:8px 4px;">
                                @csrf @method('PUT')
                                <select name="class_level_id" style="flex:1; padding:6px 8px; border:1px solid var(--border); border-radius:4px; {{ !$teacher->class_level_id ? 'border-color:#f59e0b;' : '' }}">
                                    @if (!$teacher->class_level_id)
                                        <option value="" disabled selected>⚠️ Not linked — was: "{{ $teacher->assigned_class }}"</option>
                                    @endif
                                    @foreach ($classLevels as $cl)
                                        <option value="{{ $cl->id }}" {{ $teacher->class_level_id == $cl->id ? 'selected' : '' }}>{{ $cl->displayName() }} ({{ $cl->curriculum->code ?? 'no curriculum' }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" style="font-size:11px; padding:5px 12px; border:1px solid var(--border); border-radius:6px; background:white; white-space:nowrap;">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:24px;">No teachers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
