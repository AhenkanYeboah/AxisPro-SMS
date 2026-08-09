@extends('layouts.dashboard')

@section('title', 'Report Cards')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Report Cards')
@section('welcome-message', 'Class: ' . ($teacher->assigned_class ?: 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}" class="active"><i class="nav-icon">📊</i> Report Cards</a>
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
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📊 Upload Report Card — {{ $teacher->assigned_class }}</h4>

            <form method="POST" action="{{ route('teacher.report-cards.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; align-items:end;">
                @csrf
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Student *</label>
                    <select name="student_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">Select student…</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->fullName() }} ({{ $student->student_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Term *</label>
                    <input type="text" name="term" placeholder="e.g., Term 1, Term 2, etc." required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">File *</label>
                    <input type="file" name="report_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required style="width:100%; padding:6px 0;">
                    <p style="font-size:11px; color:var(--muted); margin-top:4px;">Allowed: PDF, DOC, DOCX, JPG, PNG. Max 5MB.</p>
                </div>
                <div style="grid-column:1 / -1;">
                    <button type="submit" class="btn-filter">⬆ Upload Report Card</button>
                    <span style="font-size:12px; color:var(--muted); margin-left:8px;">Re-uploading for the same student and term replaces the existing file.</span>
                </div>
            </form>
        </div>

        <div class="card card-padded">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📋 Uploaded Report Cards</h4>

            @if ($reportCards->isNotEmpty())
                <table>
                    <thead>
                        <tr><th>Student</th><th>Term</th><th>Uploaded</th><th>File</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($reportCards as $reportCard)
                            <tr>
                                <td><strong>{{ $reportCard->student->fullName() }}</strong><br><span style="font-size:11px; color:var(--muted);">{{ $reportCard->student->student_id }}</span></td>
                                <td>{{ $reportCard->term }}</td>
                                <td>{{ $reportCard->uploaded_at?->format('M d, Y') }}</td>
                                <td><a href="{{ asset('storage/' . $reportCard->file_path) }}" target="_blank">📎 View</a></td>
                                <td>
                                    <form method="POST" action="{{ route('teacher.report-cards.destroy', $reportCard) }}" style="display:inline;" onsubmit="return confirm('Delete this report card?');">
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
                <p style="color:var(--muted);">No report cards uploaded yet.</p>
            @endif
        </div>
    @endif
@endsection
