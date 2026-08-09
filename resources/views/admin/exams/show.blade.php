@extends('layouts.dashboard')

@section('title', $exam->title)
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Entrance Exams')
@section('welcome-message', $exam->title)

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}" class="active"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
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

    <div class="card card-padded" style="margin-bottom:24px;">
        <a href="{{ route('admin.exams.index') }}" style="font-size:13px;">← Back to all exams</a>
        <h3>{{ $exam->title }}</h3>
        @if ($exam->instructions)
            <p style="font-size:13px; color:var(--muted);">{{ $exam->instructions }}</p>
        @endif
        @if ($exam->file_path)
            <p><a href="{{ asset('storage/' . $exam->file_path) }}" target="_blank">📎 {{ $exam->file_original_name ?? 'Download question paper' }}</a></p>
        @endif
        @if ($exam->questions)
            <ol style="font-size:14px;">
                @foreach ($exam->questions as $q)
                    <li>{{ $q }}</li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">Submissions ({{ $submissions->count() }})</h3>
        @forelse ($submissions as $submission)
            <div style="border-top:1px solid var(--border); padding:16px 0;">
                <p style="margin:0 0 6px;">
                    <strong>{{ $submission->student->fullName() }}</strong>
                    <span style="color:var(--muted); font-size:12px;">({{ $submission->student->student_id }}) — submitted {{ $submission->submitted_at->format('M j, Y g:ia') }}</span>
                </p>

                @if ($submission->answers)
                    <ol style="font-size:13px;">
                        @foreach ($exam->questions ?? [] as $i => $q)
                            <li>
                                <em>{{ $q }}</em><br>
                                <span>{{ $submission->answers[$i] ?? '(no answer)' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($submission->answer_text)
                    <p style="font-size:13px;"><strong>Written answer:</strong><br>{{ $submission->answer_text }}</p>
                @endif

                @if ($submission->answer_file)
                    <p style="font-size:13px;"><a href="{{ asset('storage/' . $submission->answer_file) }}" target="_blank">📎 View uploaded answer file</a></p>
                @endif

                <form method="POST" action="{{ route('admin.exam-submissions.grade', $submission) }}" style="display:flex; gap:10px; align-items:flex-end; margin-top:10px; flex-wrap:wrap;">
                    @csrf
                    <div class="form-group" style="margin:0;">
                        <label>Score (0–100)</label>
                        <input type="number" name="score" min="0" max="100" step="0.5" value="{{ $submission->score }}" style="width:100px;">
                    </div>
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>Feedback</label>
                        <input type="text" name="feedback" value="{{ $submission->feedback }}">
                    </div>
                    <button type="submit" class="btn-action btn-primary">Save</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted); font-size:13px;">No applicants have submitted this exam yet.</p>
        @endforelse
    </div>
@endsection
