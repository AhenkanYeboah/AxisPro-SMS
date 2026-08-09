@extends('layouts.dashboard')

@section('title', 'Timetable')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Timetable')
@section('welcome-message', 'Class: ' . ($teacher->assigned_class ?: 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}" class="active"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}"><i class="nav-icon">📊</i> Report Cards</a>
    <a href="{{ route('teacher.research-assistant') }}"><i class="nav-icon">🔎</i> Research Assistant</a>
    <a href="{{ route('teacher.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">👤 <strong>{{ $teacher->teacher_id }}</strong></span>
    <form method="POST" action="{{ route('teacher.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
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
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">🗓 Upload Timetable — {{ $teacher->assigned_class }}</h4>

            <form method="POST" action="{{ route('teacher.timetable.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:end;">
                @csrf
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                    <input type="text" name="title" placeholder="e.g., Term 3 Class Timetable" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional notes..." style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px; font-family:inherit;"></textarea>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">File *</label>
                    <input type="file" name="timetable_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" required style="width:100%; padding:6px 0;">
                    <p style="font-size:11px; color:var(--muted); margin-top:4px;">Allowed: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX. Max 5MB.</p>
                </div>
                <div>
                    <button type="submit" class="btn-filter">⬆ Upload Timetable</button>
                </div>
            </form>
        </div>

        <div class="card card-padded">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📋 Uploaded Timetables</h4>

            @if ($timetables->isNotEmpty())
                <table>
                    <thead>
                        <tr><th>Title</th><th>Description</th><th>Uploaded</th><th>File</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($timetables as $timetable)
                            <tr>
                                <td><strong>{{ $timetable->title }}</strong></td>
                                <td>{{ \Illuminate\Support\Str::limit($timetable->description, 60) ?: '—' }}</td>
                                <td>{{ $timetable->uploaded_at?->format('M d, Y') }}</td>
                                <td><a href="{{ asset('storage/' . $timetable->file_path) }}" target="_blank">📎 View</a></td>
                                <td>
                                    <form method="POST" action="{{ route('teacher.timetable.destroy', $timetable) }}" style="display:inline;" onsubmit="return confirm('Delete this timetable?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="del-btn">🗑 Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:var(--muted);">No timetables uploaded yet.</p>
            @endif
        </div>
    @endif
@endsection
