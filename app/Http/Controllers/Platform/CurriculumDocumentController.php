<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Jobs\IngestCurriculumDocumentJob;
use App\Models\Curriculum;
use App\Models\CurriculumDocument;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Platform-admin-only (this is you, not a school admin): syllabus
 * documents are the RAG grounding source for the research assistant and
 * are shared across every school on a curriculum (see the
 * curriculum_documents migration), so managing them belongs at the
 * platform level, same as managing curricula/subjects themselves.
 */
class CurriculumDocumentController extends Controller
{
    public function index(): View
    {
        return view('platform.curriculum-documents.index', [
            'documents' => CurriculumDocument::with(['curriculum', 'subject'])
                ->withCount('chunks')
                ->latest()
                ->get(),
            'curricula' => Curriculum::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    // Chunk review/spot-check screen - SyllabusIngestionService's
    // strand/sub-strand/indicator parsing is heuristic (built from reading
    // one real syllabus document's structure, not validated against every
    // possible layout), so this exists specifically so an admin can catch
    // a badly-split or mis-tagged chunk before it starts feeding
    // generation results, and fix or remove it directly rather than
    // needing raw DB access.
    public function show(CurriculumDocument $document): View
    {
        return view('platform.curriculum-documents.show', [
            'document' => $document,
            'chunks' => $document->chunks()->orderBy('chunk_index')->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'curriculum_id' => 'required|exists:curricula,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:200',
            'document_type' => 'required|in:syllabus,dok_reference,other',
            'source_url' => 'nullable|url|max:500',
            'file' => 'required|file|mimes:pdf|max:20480', // 20MB - NaCCA syllabus PDFs run large with embedded exemplar images
        ]);

        $filePath = $request->file('file')->store('curriculum-documents', 'public');

        $document = CurriculumDocument::create([
            'curriculum_id' => $data['curriculum_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'title' => $data['title'],
            'file_path' => $filePath,
            'source_url' => $data['source_url'] ?? null,
            'document_type' => $data['document_type'],
            'ingestion_status' => 'pending',
            'uploaded_by_platform_admin_id' => Auth::guard('platform')->id(),
        ]);

        IngestCurriculumDocumentJob::dispatch($document);

        return redirect()->route('platform.curriculum-documents.index')
            ->with('success', "\"{$document->title}\" uploaded - chunking is running in the background, refresh in a moment to see progress.");
    }

    // Re-runs ingestion, e.g. after fixing an upload that failed, or after
    // improving SyllabusIngestionService's chunking logic and wanting
    // existing documents to benefit without a full re-upload.
    public function reingest(CurriculumDocument $document): RedirectResponse
    {
        IngestCurriculumDocumentJob::dispatch($document);

        return redirect()->route('platform.curriculum-documents.index')
            ->with('success', "Re-ingesting \"{$document->title}\".");
    }

    public function destroy(CurriculumDocument $document): RedirectResponse
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        $document->delete(); // chunks cascade via FK

        return redirect()->route('platform.curriculum-documents.index')
            ->with('success', 'Document removed.');
    }

    // Editing a chunk in place - the direct fix for a chunk the ingestion
    // heuristics split or tagged wrong, found via the review screen above.
    public function updateChunk(Request $request, \App\Models\CurriculumDocumentChunk $chunk): RedirectResponse
    {
        $data = $request->validate([
            'class_tag' => 'nullable|string|max:30',
            'strand' => 'nullable|string|max:150',
            'sub_strand' => 'nullable|string|max:150',
            'indicator_code' => 'nullable|string|max:40',
            'content' => 'required|string',
        ]);

        $chunk->update($data);

        return back()->with('success', 'Chunk updated.');
    }

    public function destroyChunk(\App\Models\CurriculumDocumentChunk $chunk): RedirectResponse
    {
        $chunk->delete();

        return back()->with('success', 'Chunk removed.');
    }
}
