@extends('layouts.dashboard')

@section('title', 'Library')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Library')
@section('welcome-message', 'Class: ' . ($teacher->classLevel?->displayName() ?: 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.library') }}" class="active"><i class="nav-icon">📚</i> Library</a>
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
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    @if ($noClassAssigned)
        <div class="message error">You don't have a class assigned yet — ask your school admin to assign you one before uploading library materials.</div>
    @else
        <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Materials you upload here go straight to your class's library on the student portal.</p>

        {{-- UPLOAD --}}
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📤 Upload Material</h4>
            <form method="POST" action="{{ route('teacher.library.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                @csrf
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                    <input type="text" name="title" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Description</label>
                    <textarea name="description" rows="2" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px; font-family:inherit;"></textarea>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Category *</label>
                    <select name="category" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="worksheet">Worksheet</option>
                        <option value="past_question">Past Question</option>
                        <option value="research">Research Material</option>
                        <option value="supplementary" selected>Supplementary Reading</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject (optional)</label>
                    <select name="subject_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">Any subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">File * <span style="font-weight:400; text-transform:none;">(PDF, Word, PowerPoint, ePub, or text — max 20MB)</span></label>
                    <input type="file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.epub,.txt" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:12px; display:flex; align-items:center; gap:6px; margin-top:22px;">
                        <input type="checkbox" name="allow_download" value="1" checked> Allow students to download
                    </label>
                </div>
                <div>
                    <button type="submit" class="auth-btn">Upload</button>
                </div>
            </form>
        </div>

        {{-- MY UPLOADS --}}
        <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
            <table style="min-width:700px;">
                <thead>
                    <tr><th>Title</th><th>Category</th><th>Subject</th><th>Size</th><th>Added</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        <tr>
                            <td>{{ $material->title }}{{ $material->allow_download ? '' : ' 🔒' }}</td>
                            <td>{{ str_replace('_', ' ', $material->category) }}</td>
                            <td>{{ $material->subject->name ?? '—' }}</td>
                            <td>{{ $material->formattedSize() }}</td>
                            <td>{{ $material->created_at->format('d M Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('teacher.library.destroy', $material) }}" onsubmit="return confirm('Remove this material?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center; color:var(--muted); padding:24px;">You haven't uploaded any materials yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
