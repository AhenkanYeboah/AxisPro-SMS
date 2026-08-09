@extends('layouts.dashboard')

@section('title', $student->fullName())
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Dashboard · Student Record')
@section('welcome-message', 'Applicant Profile')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}" class="active"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
    <a href="{{ route('admin.settings') }}"><i class="nav-icon">🎨</i> Settings</a>
@endsection

@section('topbar-right')
    <span class="user-greeting">👋 <strong>{{ auth('admin')->user()->full_name }}</strong></span>
    <a href="{{ route('admin.dashboard') }}" class="auth-btn" style="border-color:var(--gold);color:var(--gold);">📊 Dashboard</a>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    <div class="card" style="overflow:hidden;">
        <div class="view-hero">
            @if ($student->profile_image)
                <img src="{{ asset('storage/' . $student->profile_image) }}" class="profile-img" alt="Profile">
            @else
                <div class="profile-img" style="background:rgba(201,168,76,0.15); display:flex; align-items:center; justify-content:center; font-size:40px;">👤</div>
            @endif
            <div class="view-hero-text">
                <h1>{{ $student->fullName() }}</h1>
                <p>Student ID: {{ $student->student_id ?: 'Not set' }}</p>
            </div>
        </div>

        <div class="info-grid" style="border-top:1px solid var(--border);">
            <div class="info-card">
                <p class="info-card-title">Personal Information</p>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value">{{ $student->email }}</span></div>
                <div class="info-row"><span class="info-label">DOB</span><span class="info-value">{{ $student->date_of_birth?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Gender</span><span class="info-value">{{ $student->gender ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Phone</span><span class="info-value">{{ $student->phone ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Address</span><span class="info-value">{!! nl2br(e($student->address ?? '—')) !!}</span></div>
                <div class="info-row"><span class="info-label">Next of Kin</span><span class="info-value">{{ $student->next_of_kin ?: '—' }}</span></div>
                <div class="info-row"><span class="info-label">Class</span><span class="info-value">{{ $student->class ?: '—' }}</span></div>
                @if ($student->results_file)
                    <div class="info-row"><span class="info-label">Results File</span><span class="info-value"><a href="{{ asset('storage/' . $student->results_file) }}" target="_blank" style="color:var(--green-deep);">📄 Download</a></span></div>
                @endif
            </div>
            <div class="info-card">
                <p class="info-card-title">Admission & Exam</p>
                <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span></div>
                <div class="info-row"><span class="info-label">Exam Date</span><span class="info-value">{{ $student->exam_date?->format('M d, Y') ?? 'Not set' }}</span></div>
                <div class="info-row"><span class="info-label">Assigned Exam</span><span class="info-value">{{ $student->exam->title ?? 'Not assigned' }}</span></div>
                <div class="info-row"><span class="info-label">Exam Completed</span><span class="info-value">{{ $student->exam_completed ? '✅ Yes' : '❌ No' }}</span></div>
                <div class="info-row"><span class="info-label">Exam Verified</span><span class="info-value">{{ $student->exam_verified ? '✅ Yes' : '❌ No' }}</span></div>
            </div>
        </div>

        <div style="padding: 0 28px 20px;">
            <h4 style="font-size:13px; font-weight:600; color:var(--green-deep); margin-bottom:12px;">Admin Actions</h4>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <form method="POST" action="{{ route('admin.students.exam-date', $student) }}" style="display:inline-flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    @csrf
                    <input type="date" name="exam_date" value="{{ $student->exam_date?->format('Y-m-d') }}" required>
                    <select name="exam_id">
                        <option value="">— No exam assigned —</option>
                        @foreach ($exams as $examOption)
                            <option value="{{ $examOption->id }}" @selected($student->exam_id === $examOption->id)>{{ $examOption->title }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-action btn-primary">Set Exam Date & Assign</button>
                </form>
                @if ($exams->isEmpty())
                    <span style="font-size:12px; color:var(--muted);">No exams in the bank yet — <a href="{{ route('admin.exams.index') }}">create one</a> first.</span>
                @endif

                @if ($examSubmission)
                    <a href="{{ route('admin.exams.show', $student->exam_id) }}" class="btn-action btn-success" style="text-decoration:none;">📄 View & Grade Submission</a>
                @elseif (!$student->exam_completed)
                    <form method="POST" action="{{ route('admin.students.exam-completed', $student) }}" onsubmit="return confirm('Mark exam as completed? Use this only if the exam was taken on paper, outside the system.');" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-action btn-warning">📝 Mark Exam Done (paper exam)</button>
                    </form>
                @else
                    <span style="font-size:13px; color:var(--muted);">✅ Exam already marked completed</span>
                @endif

                @if ($student->status !== 'active')
                    <form method="POST" action="{{ route('admin.students.verify', $student) }}" onsubmit="return confirm('Verify this student? This will grant full access.');" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-action btn-success">✅ Verify & Admit</button>
                    </form>
                @else
                    <span style="font-size:13px; color:var(--muted);">✅ Already verified</span>
                @endif

                @if ($student->status !== 'declined')
                    <form method="POST" action="{{ route('admin.students.decline', $student) }}" onsubmit="return confirm('Decline this student? They will lose access.');" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-action btn-danger">🚫 Decline</button>
                    </form>
                @else
                    <span style="font-size:13px; color:var(--muted);">⛔ Already declined</span>
                @endif
            </div>
        </div>

        <div class="view-footer">
            <a href="{{ route('admin.dashboard') }}" class="btn-back">← Back to Dashboard</a>
            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Delete this applicant permanently?');" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="delete-btn">🗑 Delete</button>
            </form>
        </div>
    </div>
@endsection
