@extends('layouts.dashboard')

@section('title', 'Research Assistant')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Research Assistant')
@section('welcome-message', 'Class: ' . ($classLevel?->displayName() ?? 'Not assigned'))

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}"><i class="nav-icon">📊</i> Report Cards</a>
    <a href="{{ route('teacher.research-assistant') }}" class="active"><i class="nav-icon">🔎</i> Research Assistant</a>
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
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    @if ($noClassAssigned)
        <div class="card card-padded">
            <p style="color:var(--muted);">You don't have a class assigned. Please contact the admin.</p>
        </div>
    @elseif ($noCurriculumAssigned)
        <div class="card card-padded">
            <p style="color:var(--muted);">Your class (<strong>{{ $classLevel->displayName() }}</strong>) isn't linked to a curriculum yet. Ask your school admin to set this in Class settings — the research assistant needs to know which curriculum (GES, Cambridge, etc.) your class follows before it can generate grounded material.</p>
        </div>
    @else
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:6px;">🔎 Request Teaching Material — {{ $classLevel->displayName() }} ({{ $classLevel->curriculum->name }})</h4>
            <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Material is generated strictly from your curriculum's uploaded syllabus content — if nothing matches your topic, you'll get a clear message instead of a guessed answer.</p>
            <p style="font-size:11px; color:{{ $usedToday >= $dailyLimit ? '#b91c1c' : 'var(--muted)' }}; margin-bottom:16px;">{{ $usedToday }} / {{ $dailyLimit }} requests used today.</p>

            <form method="POST" action="{{ route('teacher.research-assistant.store') }}" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:end;">
                @csrf
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject *</label>
                    <select name="subject_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="">Select subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Material Type *</label>
                    <select name="material_type" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                        <option value="lesson_note" {{ old('material_type') == 'lesson_note' ? 'selected' : '' }}>Lesson Note</option>
                        <option value="worksheet" {{ old('material_type') == 'worksheet' ? 'selected' : '' }}>Worksheet</option>
                        <option value="quiz" {{ old('material_type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                        <option value="exam" {{ old('material_type') == 'exam' ? 'selected' : '' }}>Exam Questions</option>
                    </select>
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Topic / Strand *</label>
                    <input type="text" name="topic" value="{{ old('topic') }}" placeholder="e.g. The Water Cycle, or the exact strand/sub-strand name from the syllabus" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <button type="submit" class="auth-btn" style="width:100%;">Generate</button>
                </div>
            </form>
            <p style="font-size:11px; color:var(--muted); margin-top:10px;">💡 Rigor level (DOK) isn't something you choose — the assistant reads the actual syllabus indicators for this topic and matches whatever depth of thinking GES/NaCCA's own curriculum calls for. A single topic may span more than one level at once.</p>
        </div>

        <div class="card card-padded">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">Recent Requests</h4>

            @forelse ($requests as $req)
                <div style="border:1.5px solid var(--border); border-radius:8px; padding:14px; margin-bottom:12px; {{ $req->id == session('generated_request_id') ? 'border-color:var(--green-deep);' : '' }}">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:8px;">
                        <div>
                            <strong>{{ $req->topic }}</strong>
                            <span style="font-size:11px; color:var(--muted);">— {{ $req->subject->name }} · {{ str_replace('_', ' ', $req->material_type) }}</span>
                            @foreach ($req->assigned_dok_levels ?? [] as $level)
                                <span style="font-size:11px; background:var(--green-light,#eef7ee); color:var(--green-deep); padding:2px 8px; border-radius:10px; margin-left:4px;">DOK {{ $level }}</span>
                            @endforeach
                        </div>
                        <span style="font-size:11px; color:var(--muted);">{{ $req->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($req->status === 'completed')
                        <div style="white-space:pre-wrap; font-size:13px; line-height:1.5; background:var(--bg-soft,#fafafa); padding:12px; border-radius:6px; max-height:{{ $req->id == session('generated_request_id') ? 'none' : '160px' }}; overflow:hidden;">{{ $req->generated_content }}</div>

                        @if ($req->marked_helpful === null)
                            <form method="POST" action="{{ route('teacher.research-assistant.helpful', $req) }}" style="display:inline-flex; gap:8px; margin-top:8px;">
                                @csrf
                                <input type="hidden" name="helpful" value="1">
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid var(--border); border-radius:6px; background:white;">👍 Helpful</button>
                            </form>
                            <form method="POST" action="{{ route('teacher.research-assistant.helpful', $req) }}" style="display:inline-flex; gap:8px;">
                                @csrf
                                <input type="hidden" name="helpful" value="0">
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid var(--border); border-radius:6px; background:white;">👎 Not quite</button>
                            </form>
                        @endif

                        @if (!empty($req->source_chunk_ids))
                            <details style="margin-top:10px;">
                                <summary style="font-size:11px; color:var(--green-deep); cursor:pointer; font-weight:600;">📎 View sources ({{ count($req->source_chunk_ids) }})</summary>
                                <div style="margin-top:8px; display:flex; flex-direction:column; gap:8px;">
                                    @foreach ($req->source_chunk_ids as $chunkId)
                                        @php $sourceChunk = $chunksById->get($chunkId); @endphp
                                        @if ($sourceChunk)
                                            <div style="border-left:3px solid var(--green-deep); padding:6px 10px; background:var(--bg-soft,#fafafa); font-size:12px;">
                                                <div style="color:var(--muted); margin-bottom:4px;">
                                                    {{ $sourceChunk->indicator_code ?? 'No indicator code' }}
                                                    @if ($sourceChunk->strand) — {{ $sourceChunk->strand }} @endif
                                                    @if ($sourceChunk->sub_strand) / {{ $sourceChunk->sub_strand }} @endif
                                                </div>
                                                <div style="white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit($sourceChunk->content, 300) }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @elseif ($req->status === 'failed')
                        <p style="font-size:12px; color:#b45309;">⚠️ {{ $req->error_message }}</p>
                    @else
                        <p style="font-size:12px; color:var(--muted);">Generating…</p>
                    @endif
                </div>
            @empty
                <p style="color:var(--muted); font-size:13px;">No requests yet — use the form above to generate your first piece of material.</p>
            @endforelse
        </div>
    @endif
@endsection
