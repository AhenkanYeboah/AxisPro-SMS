@extends('layouts.dashboard')

@section('title', 'Review Chunks — ' . $document->title)
@section('sidebar-sub', 'Platform Admin')
@section('page-label', 'Review: ' . $document->title)
@section('welcome-message', $chunks->total() . ' chunks — spot-check before trusting these in generation')

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

    <p style="margin-bottom:16px;"><a href="{{ route('platform.curriculum-documents.index') }}">← Back to documents</a></p>

    @if ($document->ingestion_status === 'failed')
        <div class="message error">Ingestion failed: {{ $document->ingestion_error }}</div>
    @endif

    @forelse ($chunks as $chunk)
        <div class="card card-padded" style="margin-bottom:16px;">
            <form method="POST" action="{{ route('platform.curriculum-document-chunks.update', $chunk) }}">
                @csrf @method('PUT')
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:10px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Class Tag</label>
                        <input type="text" name="class_tag" value="{{ $chunk->class_tag }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Indicator Code</label>
                        <input type="text" name="indicator_code" value="{{ $chunk->indicator_code }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Page</label>
                        <input type="text" value="{{ $chunk->page_number }}" disabled style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px; background:#f5f5f5;">
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Strand</label>
                        <input type="text" name="strand" value="{{ $chunk->strand }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;">
                    </div>
                    <div style="grid-column:span 2;">
                        <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Sub-strand</label>
                        <input type="text" name="sub_strand" value="{{ $chunk->sub_strand }}" style="width:100%; padding:6px 8px; border:1px solid var(--border); border-radius:4px;">
                    </div>
                </div>
                <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Content</label>
                <textarea name="content" rows="4" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:4px; font-family:inherit; font-size:13px;">{{ $chunk->content }}</textarea>

                <div style="margin-top:10px; display:flex; gap:8px;">
                    <button type="submit" style="font-size:11px; padding:5px 12px; border:1px solid var(--border); border-radius:6px; background:white;">Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('platform.curriculum-document-chunks.destroy', $chunk) }}" onsubmit="return confirm('Remove this chunk?');" style="margin-top:6px;">
                @csrf @method('DELETE')
                <button type="submit" style="font-size:11px; padding:5px 12px; border:1px solid #fecaca; border-radius:6px; background:white; color:#b91c1c;">Delete</button>
            </form>
        </div>
    @empty
        <div class="card card-padded"><p style="color:var(--muted);">No chunks yet — ingestion may still be processing, or failed. Try re-ingesting from the documents list.</p></div>
    @endforelse

    <div style="margin-top:16px;">{{ $chunks->links() }}</div>
@endsection
