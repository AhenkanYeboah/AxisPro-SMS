@extends('layouts.dashboard')

@section('title', $material->title)
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
    <a href="{{ route('student.library') }}" style="font-size:12px; text-decoration:none; color:var(--text,#111);">← Back to Library</a>
@endsection

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <div>
            <h3 style="font-size:18px; font-weight:700; margin-bottom:4px;">{{ $material->title }}</h3>
            @if ($material->description)
                <p style="font-size:12px; color:var(--muted);">{{ $material->description }}</p>
            @endif
        </div>
        @if ($material->allow_download)
            <a href="{{ route('student.library.download', $material) }}" class="auth-btn" style="font-size:12px; padding:8px 16px;">⬇ Download</a>
        @endif
    </div>

    @if ($material->file_type === 'pdf')
        <div class="card" style="padding:0; overflow:hidden; height:80vh;">
            <iframe src="{{ route('student.library.stream', $material) }}" style="width:100%; height:100%; border:none;" title="{{ $material->title }}"></iframe>
        </div>
    @else
        <div class="card card-padded" style="text-align:center; padding:48px 24px;">
            <div style="font-size:40px; margin-bottom:12px;">📄</div>
            <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">
                This {{ strtoupper($material->file_type) }} file can't be previewed in the browser.
                @if ($material->allow_download)
                    Download it to read it.
                @else
                    Ask your teacher for another way to access it.
                @endif
            </p>
            @if ($material->allow_download)
                <a href="{{ route('student.library.download', $material) }}" class="auth-btn" style="padding:10px 20px;">⬇ Download {{ $material->title }}</a>
            @endif
        </div>
    @endif
@endsection
