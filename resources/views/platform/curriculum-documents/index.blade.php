@extends('layouts.dashboard')

@section('title', 'Curriculum Documents — AxisPro School Management System')
@section('sidebar-sub', 'Platform Admin')
@section('page-label', 'Curriculum Documents')
@section('welcome-message', 'Syllabus sources for the research assistant')

@section('nav-links')
    <a href="{{ route('platform.dashboard') }}"><i class="nav-icon">▤</i> Schools</a>
    <a href="{{ route('platform.curriculum-documents.index') }}" class="active"><i class="nav-icon">📚</i> Curriculum Documents</a>
    <a href="{{ route('platform.curriculum-exemplars.index') }}"><i class="nav-icon">⭐</i> Exemplars</a>
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

    <div class="card card-padded" style="margin-bottom:24px;">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:6px;">📚 Upload Syllabus Document</h4>
        <p style="font-size:12px; color:var(--muted); margin-bottom:16px;">Uploaded once, shared by every school on that curriculum. Chunking runs in the background — refresh after a minute to see it move to "Completed".</p>

        <form method="POST" action="{{ route('platform.curriculum-documents.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:end;">
            @csrf
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Curriculum *</label>
                <select name="curriculum_id" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                    <option value="">Select curriculum</option>
                    @foreach ($curricula as $curriculum)
                        <option value="{{ $curriculum->id }}">{{ $curriculum->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Subject (leave blank if document spans multiple)</label>
                <select name="subject_id" style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                    <option value="">— Not subject-specific —</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->curriculum->code }} — {{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1 / -1;">
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                <input type="text" name="title" placeholder="e.g. NaCCA Science Curriculum (B7-B9)" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Document Type *</label>
                <select name="document_type" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                    <option value="syllabus">Syllabus</option>
                    <option value="dok_reference">DOK / Cognitive Rigour Reference</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Source URL (optional, for provenance)</label>
                <input type="url" name="source_url" placeholder="https://nacca.gov.gh/..." style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
            </div>
            <div style="grid-column:1 / -1;">
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">PDF File * (max 20MB)</label>
                <input type="file" name="file" accept=".pdf" required style="width:100%; padding:6px 0;">
            </div>
            <div style="grid-column:1 / -1;">
                <button type="submit" class="auth-btn">Upload & Ingest</button>
            </div>
        </form>
    </div>

    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
        <table style="min-width:900px;">
            <thead>
                <tr>
                    <th>Title</th><th>Curriculum</th><th>Subject</th><th>Type</th><th>Chunks</th><th>Status</th><th>Uploaded</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    <tr>
                        <td>{{ $doc->title }}</td>
                        <td>{{ $doc->curriculum->code }}</td>
                        <td>{{ $doc->subject->name ?? '—' }}</td>
                        <td>{{ str_replace('_', ' ', $doc->document_type) }}</td>
                        <td>{{ $doc->chunks_count }}</td>
                        <td>
                            @if ($doc->ingestion_status === 'completed')
                                <span style="color:var(--green-deep); font-weight:600;">✅ Completed</span>
                            @elseif ($doc->ingestion_status === 'failed')
                                <span style="color:#b91c1c; font-weight:600;" title="{{ $doc->ingestion_error }}">❌ Failed</span>
                            @elseif ($doc->ingestion_status === 'processing')
                                <span style="color:#b45309;">⏳ Processing</span>
                            @else
                                <span style="color:var(--muted);">Pending</span>
                            @endif
                        </td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('platform.curriculum-documents.show', $doc) }}" style="font-size:11px; padding:4px 10px; border:1px solid var(--border); border-radius:6px; background:white; display:inline-block; text-decoration:none; color:inherit;">Review</a>
                            <form method="POST" action="{{ route('platform.curriculum-documents.reingest', $doc) }}" style="display:inline;">
                                @csrf
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid var(--border); border-radius:6px; background:white;">Re-ingest</button>
                            </form>
                            <form method="POST" action="{{ route('platform.curriculum-documents.destroy', $doc) }}" style="display:inline;" onsubmit="return confirm('Remove this document and all its chunks?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:11px; padding:4px 10px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; color:var(--muted); padding:24px;">No documents uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
