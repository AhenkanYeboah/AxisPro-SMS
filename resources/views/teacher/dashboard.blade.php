@extends('layouts.dashboard')

@section('title', 'Teacher Dashboard')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Teacher Dashboard')
@section('welcome-message', 'Welcome, ' . $teacher->full_name . ' 👋')

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}" class="active"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
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
    <div class="card card-padded" style="margin-bottom:24px; background:var(--gold-light); border-left:4px solid var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <strong style="font-size:16px; color:var(--green-deep);">📘 Assigned Class</strong>
                <p style="font-size:20px; font-weight:700; color:var(--green-deep); margin:4px 0 0;">
                    {{ $teacher->assigned_class ?: 'Not assigned' }}
                </p>
            </div>
            <div style="text-align:right;">
                <span style="font-size:13px; color:var(--muted);">Teacher ID</span>
                <p style="font-weight:600;">{{ $teacher->teacher_id }}</p>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">🎓</div>
            <div class="stat-info"><div class="stat-number">{{ $stats['class_size'] }}</div><div class="stat-label">Class Size</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon male">👨</div>
            <div class="stat-info"><div class="stat-number">{{ $stats['male'] }}</div><div class="stat-label">Male</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon female">👩</div>
            <div class="stat-info"><div class="stat-number">{{ $stats['female'] }}</div><div class="stat-label">Female</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">📝</div>
            <div class="stat-info"><div class="stat-number">{{ $assignments->count() }}</div><div class="stat-label">Assignments Posted</div></div>
        </div>
    </div>

    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📝 Assignments</h4>

        @if (session('status'))
            <div class="message success">✅ {{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('assignments.store') }}" class="assignment-form">
            @csrf
            <div class="full-width">
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                <input type="text" name="title" placeholder="e.g., Mathematics Homework" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div class="full-width">
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Description</label>
                <textarea name="description" rows="3" placeholder="Assignment details..." style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px; font-family:inherit;"></textarea>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Due Date *</label>
                <input type="date" name="due_date" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn-filter">➕ Post Assignment</button>
            </div>
        </form>

        @if ($assignments->isNotEmpty())
            <div class="assignment-list">
                <table>
                    <thead>
                        <tr><th>Title</th><th>Description</th><th>Due Date</th><th>Posted</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td><strong>{{ $assignment->title }}</strong></td>
                                <td>{{ \Illuminate\Support\Str::limit($assignment->description, 60) }}</td>
                                <td>{{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($assignment->created_at)->format('M d, Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" style="display:inline;" onsubmit="return confirm('Delete this assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="del-btn">🗑 Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color:var(--muted);">No assignments posted yet.</p>
        @endif
    </div>

    <div class="card card-padded">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">🎓 Class Roster — {{ $teacher->assigned_class ?: 'Not assigned' }}</h4>

        @if (session('promotion_success'))
            <div class="message success">✅ {{ session('promotion_success') }}</div>
        @endif
        @if (session('promotion_error'))
            <div class="message error">❌ {{ session('promotion_error') }}</div>
        @endif
        @if (session('promotion_info'))
            <div class="message info">ℹ️ {{ session('promotion_info') }}</div>
        @endif

        @if ($classRoster->isNotEmpty())
            <div class="teacher-class-list">
                @foreach ([['label' => '👨 Male', 'group' => $male], ['label' => '👩 Female', 'group' => $female]] as $section)
                    <div class="gender-section">
                        <div class="gender-section-header">{{ $section['label'] }} ({{ $section['group']->count() }})</div>
                        <div class="gender-section-body">
                            @forelse ($section['group'] as $student)
                                <div class="student-item">
                                    @if ($student->profile_image)
                                        <img src="{{ asset('storage/' . $student->profile_image) }}" class="student-photo" alt="">
                                    @else
                                        <span style="font-size:24px;">👤</span>
                                    @endif
                                    <div class="student-info">
                                        <div class="name">{{ $student->fullName() }}</div>
                                        <div class="email">{{ $student->email }}</div>
                                    </div>
                                    <div class="student-id">{{ $student->student_id }}</div>
                                    <div class="actions">
                                        <form method="POST" action="{{ route('teacher.students.promote', $student) }}" onsubmit="return confirm('Promote this student to the next class?');" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn-promote">↗ Promote</button>
                                        </form>
                                        <form method="POST" action="{{ route('teacher.students.repeat', $student) }}" onsubmit="return confirm('Keep this student in the same class?');" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn-repeat">↻ Repeat</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p style="color:var(--muted); padding:4px 0;">No {{ strtolower($section['label']) }} students.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color:var(--muted);">No active students in this class yet.</p>
        @endif
    </div>
@endsection
