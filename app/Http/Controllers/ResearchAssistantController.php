<?php

namespace App\Http\Controllers;

use App\Models\ResearchRequest;
use App\Models\Subject;
use App\Services\AI\TeachingMaterialGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Teacher-facing research assistant. Deliberately scoped to the teacher's
 * OWN assigned class_level (via Teacher::classLevel, see the teachers
 * migration) - a teacher can only request material for the class they
 * actually teach, and can pick any subject their school's curriculum
 * offers for that class, since (per Teacher model) there's no per-subject
 * assignment, just a per-class one - common in Ghanaian basic schools
 * where one teacher covers most subjects for their class.
 *
 * DOK level is NOT teacher-selectable, on purpose - confirmed design
 * decision: GES/NaCCA doesn't publish a per-class DOK ceiling, so letting
 * a teacher pick one would mean enforcing a rule that isn't actually
 * GES's. See TeachingMaterialGenerationService for how the AI derives it
 * instead, directly from the real indicator text it retrieves.
 */
class ResearchAssistantController extends Controller
{
    public function index(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $classLevel = $teacher->classLevel;

        $subjects = $classLevel?->curriculum_id
            ? Subject::where('curriculum_id', $classLevel->curriculum_id)->orderBy('name')->get()
            : collect();

        $requests = ResearchRequest::where('teacher_id', $teacher->id)
            ->latest()
            ->limit(10)
            ->get();

        // Source chunk IDs are stored as a plain JSON array (not a
        // relationship Eloquent can eager-load in one query across mixed
        // requests), so they're fetched here as a single extra query and
        // grouped back onto each request - avoids one query per request
        // in the view when a teacher expands "View Sources".
        $chunkIds = $requests->pluck('source_chunk_ids')->filter()->flatten()->unique()->values();
        $chunksById = \App\Models\CurriculumDocumentChunk::whereIn('id', $chunkIds)->get()->keyBy('id');

        $dailyLimit = (int) config('services.anthropic.daily_limit_per_teacher');
        $usedToday = ResearchRequest::where('teacher_id', $teacher->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return view('teacher.research-assistant', [
            'teacher' => $teacher,
            'classLevel' => $classLevel,
            'subjects' => $subjects,
            'requests' => $requests,
            'chunksById' => $chunksById,
            'dailyLimit' => $dailyLimit,
            'usedToday' => $usedToday,
            'noClassAssigned' => ! $classLevel,
            'noCurriculumAssigned' => $classLevel && ! $classLevel->curriculum_id,
        ]);
    }

    public function store(Request $request, TeachingMaterialGenerationService $generator): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $classLevel = $teacher->classLevel;

        if (! $classLevel || ! $classLevel->curriculum_id) {
            return back()->with('error', 'Your class isn\'t linked to a curriculum yet - ask your school admin to set this up in Class settings before using the research assistant.');
        }

        // Daily cap check - counts ALL of today's requests for this
        // teacher (pending/completed/failed alike), since a failed
        // request that reached the AI still cost an API call; only ones
        // rejected before calling Claude (e.g. missing curriculum, above)
        // don't count. See config/services.php for why this exists.
        $dailyLimit = (int) config('services.anthropic.daily_limit_per_teacher');
        $usedToday = ResearchRequest::where('teacher_id', $teacher->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($usedToday >= $dailyLimit) {
            return back()->with('error', "You've reached today's limit of {$dailyLimit} research assistant requests. This resets tomorrow - contact your school admin if you need more.");
        }

        $data = $request->validate([
            // Scoped to this specific curriculum, not just "any real
            // subject" - without the curriculum_id constraint, a stale or
            // tampered request could pass e.g. a Cambridge subject for a
            // GES class. That wouldn't be a security hole (retrieval would
            // just find zero matching chunks and fail cleanly), but it
            // would silently burn one of the teacher's daily-limit slots
            // on a request that could never succeed.
            'subject_id' => 'required|exists:subjects,id,curriculum_id,'.$classLevel->curriculum_id,
            'topic' => 'required|string|max:200',
            'material_type' => 'required|in:lesson_note,worksheet,quiz,exam',
            // No dok_level_id here - removed on purpose, see class docblock.
        ]);

        $subject = Subject::findOrFail($data['subject_id']);

        $researchRequest = $generator->generate(
            teacher: $teacher,
            classLevel: $classLevel,
            subject: $subject,
            topic: $data['topic'],
            materialType: $data['material_type'],
        );

        if ($researchRequest->status === 'failed') {
            return back()->with('error', $researchRequest->error_message)->withInput();
        }

        return redirect()->route('teacher.research-assistant')
            ->with('success', 'Material generated.')
            ->with('generated_request_id', $researchRequest->id);
    }

    // Lightweight feedback used to spot promising output for promotion
    // into curriculum_exemplars later - see the research_requests
    // migration comment.
    public function markHelpful(ResearchRequest $researchRequest, Request $request): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        abort_unless($researchRequest->teacher_id === $teacher->id, 403);

        $researchRequest->update([
            'marked_helpful' => $request->boolean('helpful'),
        ]);

        return back();
    }
}
