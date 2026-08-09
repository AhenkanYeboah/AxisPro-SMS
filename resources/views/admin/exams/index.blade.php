@extends('layouts.dashboard')

@section('title', 'Entrance Exams')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Entrance Exams')
@section('welcome-message', 'Entrance Exam Bank')

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
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Create a new entrance exam</h3>
        <p style="font-size:13px; color:var(--muted);">
            Build the exam by typing questions below, uploading a question paper
            (PDF/Word), or both. Once saved, assign it to an applicant from their
            profile page's "Set Exam Date" action — they'll be emailed and can log
            in to answer it online.
        </p>

        <form method="POST" action="{{ route('admin.exams.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Exam Title</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g., JHS 1 Entrance Exam — Term 1" required>
            </div>

            <div class="form-group">
                <label>Instructions for the applicant (optional)</label>
                <textarea name="instructions" rows="3" placeholder="e.g., Answer all questions. You have 45 minutes.">{{ old('instructions') }}</textarea>
            </div>

            <div class="form-group">
                <label>Typed Questions (optional)</label>
                <div id="question-list" style="display:flex; flex-direction:column; gap:8px;"></div>
                <button type="button" id="add-question" class="auth-btn" style="margin-top:8px; padding:6px 14px; font-size:12px; width:auto;">+ Add Question</button>
            </div>

            <div class="form-group">
                <label>Or upload a question paper (PDF/Word, optional)</label>
                <input type="file" name="exam_file" accept=".pdf,.doc,.docx">
                <p style="font-size:12px; color:var(--muted); margin-top:4px;">Max 10MB. Applicants can view/download this and answer via the free-text box or by uploading their own answer file.</p>
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding:10px 24px;">Save Exam</button>
        </form>
    </div>

    <div class="card card-padded">
        <h3 style="margin-top:0;">All exams</h3>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Questions</th>
                    <th>Paper</th>
                    <th>Assigned To</th>
                    <th>Submissions</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td><a href="{{ route('admin.exams.show', $exam) }}">{{ $exam->title }}</a></td>
                        <td>{{ $exam->questions ? count($exam->questions) : 0 }}</td>
                        <td>{{ $exam->file_path ? '📎 Yes' : '—' }}</td>
                        <td>{{ $exam->assigned_students_count }}</td>
                        <td>{{ $exam->submissions_count }}</td>
                        <td>{{ $exam->created_at->format('M j, Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" onsubmit="return confirm('Delete this exam? This removes it from any applicant it is assigned to.');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="auth-btn" style="padding:4px 10px; font-size:11px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--muted);">No exams created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        (function () {
            const list = document.getElementById('question-list');
            const addBtn = document.getElementById('add-question');
            let count = 0;

            function addRow(value) {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.gap = '8px';
                row.innerHTML = `
                    <input type="text" name="questions[]" placeholder="Question ${count + 1}" value="${value ? value.replace(/"/g, '&quot;') : ''}" style="flex:1;">
                    <button type="button" class="auth-btn remove-question" style="padding:6px 12px; font-size:12px; width:auto;">✕</button>
                `;
                row.querySelector('.remove-question').addEventListener('click', () => row.remove());
                list.appendChild(row);
                count++;
            }

            addBtn.addEventListener('click', () => addRow());
            addRow(); // start with one blank question
        })();
    </script>
@endsection
