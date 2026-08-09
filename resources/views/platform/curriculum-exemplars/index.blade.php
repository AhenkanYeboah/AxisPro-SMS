@extends('layouts.dashboard')

@section('title', 'Curriculum Exemplars — AxisPro School Management System')
@section('sidebar-sub', 'Platform Admin')
@section('page-label', 'Curriculum Exemplars')
@section('welcome-message', 'The curated few-shot bank for the research assistant')

@section('nav-links')
    <a href="{{ route('platform.dashboard') }}"><i class="nav-icon">▤</i> Schools</a>
    <a href="{{ route('platform.curriculum-documents.index') }}"><i class="nav-icon">📚</i> Curriculum Documents</a>
    <a href="{{ route('platform.curriculum-exemplars.index') }}" class="active"><i class="nav-icon">⭐</i> Exemplars</a>
@endsection

@section('topbar-right')
    <form method="POST" action="{{ route('platform.logout') }}" style="display:inline;">
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

    {{-- CANDIDATES: teacher-approved requests waiting to be promoted --}}
    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:6px;">👍 Promotion Candidates</h4>
        <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Requests teachers marked helpful, not yet promoted into the exemplar bank.</p>

        @forelse ($candidates as $candidate)
            <div style="border:1.5px solid var(--border); border-radius:8px; padding:14px; margin-bottom:12px;">
                <div style="margin-bottom:8px;">
                    <strong>{{ $candidate->topic }}</strong>
                    <span style="font-size:11px; color:var(--muted);">— {{ $candidate->subject->name }} · {{ str_replace('_', ' ', $candidate->material_type) }} · {{ $candidate->classLevel->displayName() }} ({{ $candidate->classLevel->curriculum->name ?? '—' }})</span>
                </div>
                <div style="white-space:pre-wrap; font-size:12px; line-height:1.5; background:var(--bg-soft,#fafafa); padding:10px; border-radius:6px; max-height:120px; overflow:hidden; margin-bottom:10px;">{{ $candidate->generated_content }}</div>

                <form method="POST" action="{{ route('platform.research-requests.promote', $candidate) }}" style="display:flex; gap:8px; align-items:center;">
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
        <form method="POST" action="{{ route('platform.curriculum-exemplars.store') }}" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
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
                <input type="text" name="class_tag" placeholder="e.g. B7" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
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

    {{-- EXISTING BANK --}}
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
                            <form method="POST" action="{{ route('platform.curriculum-exemplars.destroy', $exemplar) }}" onsubmit="return confirm('Remove this exemplar?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--muted); padding:24px;">No exemplars yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
