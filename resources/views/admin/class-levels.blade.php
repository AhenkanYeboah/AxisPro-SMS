@extends('layouts.dashboard')

@section('title', 'Classes')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Classes')
@section('welcome-message', 'Class list & curriculum assignment')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}" class="active"><i class="nav-icon">🏫</i> Classes</a>
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
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif

    @if ($schoolCurricula->isEmpty())
        <div class="message error">
            No curricula activated yet — go to <a href="{{ route('admin.settings') }}">Settings</a> and select at least one before creating or editing classes.
        </div>
    @else
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">➕ Add Class</h4>
            <form method="POST" action="{{ route('admin.class-levels.store') }}" style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px; align-items:end;">
                @csrf
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Name *</label>
                    <input type="text" name="name" placeholder="e.g. Primary 4" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Section (optional)</label>
                    <input type="text" name="section" placeholder="e.g. A" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Curriculum *</label>
                    <select name="curriculum_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        @foreach ($schoolCurricula as $curriculum)
                            <option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="auth-btn" style="width:100%;">Add</button>
                </div>
            </form>
        </div>

        <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
            <table style="min-width:800px;">
                <thead>
                    <tr>
                        <th>Class</th><th>Section</th><th>Curriculum</th><th>Order</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classLevels as $cl)
                        <tr>
                            <td colspan="5" style="padding:0;">
                                <form method="POST" action="{{ route('admin.class-levels.update', $cl) }}" style="display:grid; grid-template-columns:1fr 1fr 1.4fr 0.6fr 1fr; gap:0; align-items:center; padding:8px 4px;">
                                    @csrf @method('PUT')
                                    <div style="padding:0 6px;"><input type="text" name="name" value="{{ $cl->name }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;"></div>
                                    <div style="padding:0 6px;"><input type="text" name="section" value="{{ $cl->section }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;"></div>
                                    <div style="padding:0 6px;">
                                        <select name="curriculum_id" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px; {{ !$cl->curriculum_id ? 'border-color:#f59e0b;' : '' }}">
                                            @if (!$cl->curriculum_id)
                                                <option value="" disabled selected>⚠️ Not set — choose one</option>
                                            @endif
                                            @foreach ($schoolCurricula as $curriculum)
                                                <option value="{{ $curriculum->id }}" {{ $cl->curriculum_id == $curriculum->id ? 'selected' : '' }}>{{ $curriculum->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="padding:0 6px;"><input type="number" name="sort_order" value="{{ $cl->sort_order }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;"></div>
                                    <div style="padding:0 6px; white-space:nowrap;">
                                        <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid var(--border); border-radius:6px; background:white;">Save</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('admin.class-levels.destroy', $cl) }}" style="display:inline; padding-left:6px;" onsubmit="return confirm('Remove this class? Students/teachers pointing at it will become unassigned, not deleted.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:24px;">No classes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
