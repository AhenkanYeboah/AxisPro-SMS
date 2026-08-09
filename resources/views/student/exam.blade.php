@extends('layouts.dashboard')

@section('title', 'Entrance Exam')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Entrance Exam')
@section('welcome-message', $exam->title)

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('topbar-right')
    <span class="user-greeting">Status: <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded">
        <h3 style="margin-top:0;">{{ $exam->title }}</h3>

        @if ($exam->instructions)
            <p style="color:var(--muted);">{{ $exam->instructions }}</p>
        @endif

        @if ($exam->file_path)
            <p style="margin-bottom:20px;">
                <a href="{{ asset('storage/' . $exam->file_path) }}" target="_blank" class="btn-action btn-primary" style="text-decoration:none; display:inline-block;">
                    📎 View / Download Question Paper{{ $exam->file_original_name ? ' — '.$exam->file_original_name : '' }}
                </a>
            </p>
        @endif

        @if ($submission)
            <div class="message success" style="margin-top:12px;">
                ✅ You submitted this exam on {{ $submission->submitted_at->format('M j, Y g:ia') }}. The admin will review it.
            </div>

            @if ($exam->questions)
                <ol>
                    @foreach ($exam->questions as $i => $q)
                        <li style="margin-bottom:12px;">
                            <strong>{{ $q }}</strong><br>
                            <span style="color:var(--muted);">Your answer: {{ $submission->answers[$i] ?? '(no answer)' }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($submission->answer_text)
                <p><strong>Your written answer:</strong><br>{{ $submission->answer_text }}</p>
            @endif

            @if ($submission->score !== null)
                <p><strong>Score:</strong> {{ $submission->score }}/100</p>
            @endif
            @if ($submission->feedback)
                <p><strong>Feedback:</strong> {{ $submission->feedback }}</p>
            @endif
        @else
            <form method="POST" action="{{ route('student.exam.submit') }}" enctype="multipart/form-data">
                @csrf

                @if ($exam->questions)
                    <h4 style="color:var(--green-deep);">Questions</h4>
                    @foreach ($exam->questions as $i => $q)
                        <div class="form-group">
                            <label>{{ $i + 1 }}. {{ $q }}</label>
                            <textarea name="answers[{{ $i }}]" rows="2" required></textarea>
                        </div>
                    @endforeach
                @endif

                <div class="form-group">
                    <label>{{ $exam->file_path ? 'Written answer (use this for the question paper above, if needed)' : 'Additional comments (optional)' }}</label>
                    <textarea name="answer_text" rows="5"></textarea>
                </div>

                <div class="form-group">
                    <label>Or upload your answers as a file (photo/scan/PDF, optional)</label>
                    <input type="file" name="answer_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>

                <button type="submit" class="btn-submit" style="width:auto; padding:12px 32px;" onclick="return confirm('Submit your exam? You will not be able to change your answers after this.');">Submit Exam</button>
            </form>
        @endif
    </div>
@endsection
