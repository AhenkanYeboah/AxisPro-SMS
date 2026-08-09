@extends('layouts.dashboard')

@section('title', 'Exemplars')
@section('sidebar-sub', 'Admin Dashboard')
@section('page-label', 'Exemplars')
@section('welcome-message', "Your school's own style/format bank for the research assistant")

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
    <a href="{{ route('admin.exemplars.index') }}" class="active"><i class="nav-icon">⭐</i> Exemplars</a>
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

    <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">This is your school's own bank — separate from AxisPro's official syllabus content. Use it for your own exam formats, lesson-note style, or locally-relevant examples. It only affects material generated for your teachers.</p>

    @if ($curricula->isEmpty())
        <div class="message error">
            No curricula activated yet — go to <a href="{{ route('admin.settings') }}">Settings</a> and select at least one first.
        </div>
    @else
        {{-- CANDIDATES: this school's teacher-approved requests waiting to be promoted --}}
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:6px;">👍 Promotion Candidates</h4>
            <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Your teachers' requests marked helpful, not yet promoted.</p>

            @forelse ($candidates as $candidate)
                <div style="border:1.5px solid var(--border); border-radius:8px; padding:14px; margin-bottom:12px;">
                    <div style="margin-bottom:8px;">
                        <strong>{{ $candidate->topic }}</strong>
                        <span style="font-size:11px; color:var(--muted);">— {{ $candidate->subject->name }} · {{ str_replace('_', ' ', $candidate->material_type) }} · {{ $candidate->classLevel->displayName() }}</span>
                    </div>
                    <div style="white-space:pre-wrap; font-size:12px; line-height:1.5; background:var(--bg-soft,#fafafa); padding:10px; border-radius:6px; max-height:120px; overflow:hidden; margin-bottom:10px;">{{ $candidate->generated_content }}</div>

                    <form method="POST" action="{{ route('admin.research-requests.promote', $candidate) }}" style="display:flex; gap:8px; align-items:center;">
                        @csrf
                        <input type="text" name="title" placeholder="Exemplar title" required style="flex:1; padding:6px 10px; border:1px solid var(--border); border-radius:4px;">
                        <button type="submit" style="font-size:12px; padding:6px 14px; border:1px solid var(--green-deep); border-radius:6px; background:var(--green-deep); color:white;">Promote</button>
                    </form>
                </div>
            @empty
                <p style="color:var(--muted); font-size:13px;">No candidates right now.</p>
            @endforelse
        </div>

        {{-- AUTHOR DIRECTLY --}}
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">✍️ Author an Exemplar</h4>
            <form method="POST" action="{{ route('admin.exemplars.store') }}" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                @csrf
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Curriculum *</label>
                    <select name="curriculum_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">Select</option>
                        @foreach ($curricula as $curriculum)
                            <option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject *</label>
                    <select name="subject_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">Select</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->curriculum->code }} — {{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">DOK Level</label>
                    <select name="dok_level_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">—</option>
                        @foreach ($dokLevels as $dok)
                            <option value="{{ $dok->id }}">DOK {{ $dok->level }} — {{ $dok->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Class Tag</label>
                    <input type="text" name="class_tag" placeholder="e.g. JHS 3" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Material Type *</label>
                    <input type="text" name="material_type" placeholder="lesson_note, worksheet, quiz, exam" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                    <input type="text" name="title" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Content *</label>
                    <textarea name="content" rows="6" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px; font-family:inherit;"></textarea>
                </div>
                <div>
                    <button type="submit" class="auth-btn">Add Exemplar</button>
                </div>
            </form>
        </div>

        {{-- THIS SCHOOL'S BANK --}}
        <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
            <table style="min-width:900px;">
                <thead>
                    <tr><th>Title</th><th>Curriculum</th><th>Subject</th><th>DOK</th><th>Type</th><th>Added</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($exemplars as $exemplar)
                        <tr>
                            <td>{{ $exemplar->title }}</td>
                            <td>{{ $exemplar->curriculum->code }}</td>
                            <td>{{ $exemplar->subject->name }}</td>
                            <td>{{ $exemplar->dokLevel?->level ?? '—' }}</td>
                            <td>{{ str_replace('_', ' ', $exemplar->material_type) }}</td>
                            <td>{{ $exemplar->created_at->format('d M Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.exemplars.destroy', $exemplar) }}" onsubmit="return confirm('Remove this exemplar?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center; color:var(--muted); padding:24px;">No exemplars yet — this school is currently only grounded by AxisPro's shared syllabus content.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
