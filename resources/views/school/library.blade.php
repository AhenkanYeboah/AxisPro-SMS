@extends('layouts.dashboard')

@section('title', 'Library')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Library')
@section('welcome-message', $student->class ?: 'Not assigned')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.library') }}" class="active"><i class="nav-icon">📚</i> Library</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}"><i class="nav-icon">📊</i> Report Card</a>
    <a href="{{ route('student.fees') }}"><i class="nav-icon">💳</i> My Fees</a>
    <a href="{{ route('student.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('topbar-right')
    <span class="user-greeting">Status: <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span>
@endsection

@section('content')
    <style>
        .library-filter.active { background:var(--green-deep,#166534); color:#fff !important; border-color:var(--green-deep,#166534) !important; }
    </style>

    <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Reading materials and research resources for {{ $student->class ?: 'your class' }}.</p>

    @if ($subjects->isNotEmpty())
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
            <a href="#" data-subject="" class="library-filter active" style="font-size:12px; padding:6px 14px; border:1.5px solid var(--border); border-radius:20px; text-decoration:none; color:var(--text,#111);">All</a>
            @foreach ($subjects as $subject)
                <a href="#" data-subject="{{ $subject->id }}" class="library-filter" style="font-size:12px; padding:6px 14px; border:1.5px solid var(--border); border-radius:20px; text-decoration:none; color:var(--text,#111);">{{ $subject->name }}</a>
            @endforeach
        </div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:16px;" id="library-grid">
        @forelse ($materials as $material)
            <div class="card card-padded library-item" data-subject="{{ $material->subject_id }}" style="display:flex; flex-direction:column; gap:8px;">
                <div style="font-size:28px;">📄</div>
                <div style="font-weight:700; font-size:14px;">{{ $material->title }}</div>
                @if ($material->description)
                    <div style="font-size:12px; color:var(--muted); flex-grow:1;">{{ \Illuminate\Support\Str::limit($material->description, 90) }}</div>
                @endif
                <div style="font-size:11px; color:var(--muted);">
                    {{ str_replace('_', ' ', $material->category) }}
                    @if ($material->subject) · {{ $material->subject->name }} @endif
                    · {{ strtoupper($material->file_type) }} · {{ $material->formattedSize() }}
                </div>
                <div style="display:flex; gap:8px; margin-top:6px;">
                    <a href="{{ route('student.library.read', $material) }}" class="auth-btn" style="text-align:center; flex:1; font-size:12px; padding:8px;">📖 Read</a>
                    @if ($material->allow_download)
                        <a href="{{ route('student.library.download', $material) }}" style="text-align:center; flex:1; font-size:12px; padding:8px; border:1.5px solid var(--border); border-radius:6px; text-decoration:none; color:var(--text,#111);">⬇ Download</a>
                    @endif
                </div>
            </div>
        @empty
            <p style="grid-column:1/-1; text-align:center; color:var(--muted); padding:24px;">No materials in your library yet — check back soon.</p>
        @endforelse
    </div>

    @if ($subjects->isNotEmpty())
        <script>
            document.querySelectorAll('.library-filter').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelectorAll('.library-filter').forEach(function (l) { l.classList.remove('active'); });
                    this.classList.add('active');
                    var subjectId = this.dataset.subject;
                    document.querySelectorAll('.library-item').forEach(function (item) {
                        item.style.display = (!subjectId || item.dataset.subject === subjectId) ? '' : 'none';
                    });
                });
            });
        </script>
    @endif
@endsection
