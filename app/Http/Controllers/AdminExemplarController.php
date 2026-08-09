<?php

namespace App\Http\Controllers;

use App\Models\CurriculumExemplar;
use App\Models\DokLevel;
use App\Models\ResearchRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A school's OWN exemplar bank - separate from the platform-curated,
 * cross-school one (Platform\CurriculumExemplarController). Deliberately
 * narrower in scope than that controller: a school can only author/promote
 * into curriculum_exemplars rows where school_id = its own id, never the
 * shared school_id = null pool, and only for curricula it has actually
 * activated. See the curriculum_exemplars migration and
 * SyllabusRetrievalService::findExemplars for how both pools get queried
 * together at generation time.
 */
class AdminExemplarController extends Controller
{
    public function index(): View
    {
        $school = app('currentSchool');

        return view('admin.exemplars', [
            'exemplars' => CurriculumExemplar::where('school_id', $school->id)
                ->with(['curriculum', 'subject', 'dokLevel'])
                ->latest()
                ->get(),
            // Candidates are THIS school's own teachers' helpful requests -
            // ResearchRequest is BelongsToSchool, so this is already
            // tenant-scoped without an explicit where().
            'candidates' => ResearchRequest::with(['classLevel.curriculum', 'subject'])
                ->where('marked_helpful', true)
                ->whereNull('promoted_to_exemplar_id')
                ->latest()
                ->get(),
            'curricula' => $school->curricula()->orderBy('name')->get(),
            'subjects' => Subject::whereIn('curriculum_id', $school->curricula()->pluck('curricula.id'))->orderBy('name')->get(),
            'dokLevels' => DokLevel::orderBy('level')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = app('currentSchool');

        $data = $request->validate([
            'curriculum_id' => 'required|exists:curricula,id',
            'subject_id' => 'required|exists:subjects,id',
            'dok_level_id' => 'nullable|exists:dok_levels,id',
            'class_tag' => 'nullable|string|max:30',
            'title' => 'required|string|max:200',
            'material_type' => 'required|string|max:40',
            'content' => 'required|string',
        ]);

        $this->assertCurriculumActivated($school, $data['curriculum_id']);

        CurriculumExemplar::create($data + ['school_id' => $school->id]);

        return redirect()->route('admin.exemplars.index')->with('success', 'Exemplar added to your school\'s bank.');
    }

    public function promote(Request $request, ResearchRequest $researchRequest): RedirectResponse
    {
        abort_if($researchRequest->promoted_to_exemplar_id, 422, 'Already promoted.');
        abort_unless($researchRequest->status === 'completed' && $researchRequest->generated_content, 422, 'Nothing to promote.');

        $data = $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $exemplar = CurriculumExemplar::create([
            'curriculum_id' => $researchRequest->classLevel->curriculum_id,
            'school_id' => $researchRequest->school_id, // this school's own bank, not the shared pool
            'subject_id' => $researchRequest->subject_id,
            // See Platform\CurriculumExemplarController::promote() for why
            // this defaults to the lowest level used rather than a direct
            // column reference.
            'dok_level_id' => collect($researchRequest->assigned_dok_levels ?? [])->sort()->first()
                ? \App\Models\DokLevel::where('level', collect($researchRequest->assigned_dok_levels)->sort()->first())->value('id')
                : null,
            'class_tag' => $researchRequest->classLevel->name,
            'title' => $data['title'],
            'material_type' => $researchRequest->material_type,
            'content' => $researchRequest->generated_content,
        ]);

        $researchRequest->update(['promoted_to_exemplar_id' => $exemplar->id]);

        return redirect()->route('admin.exemplars.index')->with('success', "Promoted to your school's exemplar bank: \"{$exemplar->title}\".");
    }

    public function destroy(CurriculumExemplar $exemplar): RedirectResponse
    {
        // Guards against a school admin deleting a platform-shared row
        // (school_id null) or - since this isn't BelongsToSchool-scoped -
        // another school's row, neither of which route-model binding
        // alone would prevent.
        abort_unless($exemplar->school_id === app('currentSchool')->id, 403);

        $exemplar->delete();

        return back()->with('success', 'Exemplar removed.');
    }

    private function assertCurriculumActivated($school, int $curriculumId): void
    {
        abort_unless($school->curricula()->where('curricula.id', $curriculumId)->exists(), 422, 'This curriculum is not activated for your school.');
    }
}
