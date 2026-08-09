@extends('layouts.dashboard')

@section('title', 'School Settings')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Settings')
@section('welcome-message', 'School Branding')

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
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
    <a href="{{ route('admin.settings') }}" class="active"><i class="nav-icon">🎨</i> Settings</a>
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

    <div class="card card-padded" style="max-width:600px;">
        <p style="font-size:13px; color:var(--muted); margin-top:0;">
            This is how your school appears across your dashboard, and on your own
            homepage (except Royal Countryside Academy, whose homepage is a fixed,
            custom-built page).
        </p>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label>School Name</label>
                <input type="text" name="name" value="{{ old('name', $school->name) }}" required>
            </div>

            <div class="form-group">
                <label>Logo</label>
                @if ($school->logo_path)
                    <div style="margin-bottom:8px;">
                        <img src="{{ Storage::disk('public')->url($school->logo_path) }}" alt="Current logo" style="height:60px; width:60px; object-fit:contain; border-radius:8px; border:1px solid var(--border);">
                    </div>
                @endif
                <input type="file" name="logo" accept="image/*">
                <p style="font-size:12px; color:var(--muted); margin-top:4px;">PNG, JPG, or WEBP. Max 2MB. Leave blank to keep your current logo.</p>
            </div>

            <div class="form-group">
                <label>Accent Color</label>
                <input type="color" name="primary_color" value="{{ old('primary_color', $school->primary_color ?? '#3B1F6B') }}" style="height:42px; width:100px; padding:2px;">
                <p style="font-size:12px; color:var(--muted); margin-top:4px;">Used as the background color on your homepage's left panel.</p>
            </div>

            <div class="form-group">
                <label>Homepage Tagline</label>
                <textarea name="tagline" rows="3" placeholder="Complete your enrollment application online and track your admission status...">{{ old('tagline', $school->tagline) }}</textarea>
            </div>

            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $school->phone) }}" placeholder="+233 ...">
            </div>

            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $school->contact_email) }}" placeholder="admissions@yourschool.edu.gh">
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Save Branding</button>
        </form>
    </div>

    <div class="card card-padded" style="margin-top:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:6px;">Curricula</h4>
        <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Which curricula your school runs. This controls what's offered when creating or editing classes, and grounds the research assistant — see <a href="{{ route('admin.class-levels.index') }}">Classes</a> to assign a curriculum per class.</p>

        <form method="POST" action="{{ route('admin.settings.curricula') }}">
            @csrf
            @foreach ($curricula as $curriculum)
                <label style="display:flex; align-items:flex-start; gap:8px; font-weight:normal; margin-bottom:10px;">
                    <input type="checkbox" name="curricula[]" value="{{ $curriculum->id }}"
                        {{ in_array($curriculum->id, $activatedCurriculumIds) ? 'checked' : '' }}
                        style="margin-top:3px;">
                    <span>
                        <strong>{{ $curriculum->name }}</strong>
                        @if ($curriculum->grade_naming_convention)
                            <br><span style="font-size:11px; color:var(--muted);">{{ $curriculum->grade_naming_convention }}</span>
                        @endif
                    </span>
                </label>
            @endforeach

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Save Curricula</button>
        </form>
    </div>
@endsection
