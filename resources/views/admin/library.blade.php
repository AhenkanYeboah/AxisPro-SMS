@extends('layouts.dashboard')

@section('title', 'Library')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Library')
@section('welcome-message', "Reading materials and research resources for your students")

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.form') }}"><i class="nav-icon">✎</i> Enrollment Form</a>
    <a href="{{ route('admin.dashboard') }}"><i class="nav-icon">▤</i> Dashboard</a>
    <a href="{{ route('admin.exams.index') }}"><i class="nav-icon">📝</i> Entrance Exams</a>
    <a href="{{ route('admin.invites.index') }}"><i class="nav-icon">✉</i> Invites</a>
    <a href="{{ route('admin.fees.index') }}"><i class="nav-icon">💳</i> Billing</a>
    <a href="{{ route('admin.notices.index') }}"><i class="nav-icon">📣</i> Notices</a>
    <a href="{{ route('admin.class-levels.index') }}"><i class="nav-icon">🏫</i> Classes</a>
    <a href="{{ route('admin.teachers.index') }}"><i class="nav-icon">🧑‍🏫</i> Teachers</a>
    <a href="{{ route('admin.exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
    <a href="{{ route('admin.library.index') }}" class="active"><i class="nav-icon">📚</i> Library</a>
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
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="message error">❌ {{ $errors->first() }}</div>
    @endif

    <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Upload reading materials for students — textbooks, past questions, worksheets, or research resources. Leave class and subject blank to make something visible to your whole school; set a class to target just that class.</p>

    {{-- UPLOAD --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📤 Upload Material</h4>
        <form method="POST" action="{{ route('admin.library.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
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
                    <option value="textbook">Textbook</option>
                    <option value="past_question">Past Question</option>
                    <option value="worksheet">Worksheet</option>
                    <option value="research">Research Material</option>
                    <option value="supplementary" selected>Supplementary Reading</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Class (optional)</label>
                <select name="class_level_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                    <option value="">All classes</option>
                    @foreach ($classLevels as $classLevel)
                        <option value="{{ $classLevel->id }}">{{ $classLevel->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject (optional)</label>
                <select name="subject_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                    <option value="">Any subject</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->curriculum->code }} — {{ $subject->name }}</option>
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

    {{-- LIBRARY --}}
    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
        <table style="min-width:900px;">
            <thead>
                <tr><th>Title</th><th>Category</th><th>Class</th><th>Subject</th><th>Size</th><th>Uploaded By</th><th>Added</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($materials as $material)
                    <tr>
                        <td>{{ $material->title }}{{ $material->allow_download ? '' : ' 🔒' }}</td>
                        <td>{{ str_replace('_', ' ', $material->category) }}</td>
                        <td>{{ $material->classLevel?->displayName() ?? 'All classes' }}</td>
                        <td>{{ $material->subject->name ?? '—' }}</td>
                        <td>{{ $material->formattedSize() }}</td>
                        <td>{{ $material->uploaderName() }}</td>
                        <td>{{ $material->created_at->format('d M Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.library.destroy', $material) }}" onsubmit="return confirm('Remove this material?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; color:var(--muted); padding:24px;">No materials uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
