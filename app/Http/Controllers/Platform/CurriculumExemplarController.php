<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumExemplar;
use App\Models\DokLevel;
use App\Models\ResearchRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Two ways an exemplar gets into the bank: (1) a platform admin writes one
 * directly here, or (2) a teacher's request that got marked helpful
 * (research_requests.marked_helpful) gets promoted with one click - the
 * "candidates" list below is exactly that queue. Either path lands in the
 * same curriculum_exemplars table SyllabusRetrievalService already reads
 * from, so promoting a request has an immediate effect on future
 * generations for that curriculum/subject.
 */
class CurriculumExemplarController extends Controller
{
    public function index(): View
    {
        return view('platform.curriculum-exemplars.index', [
            'exemplars' => CurriculumExemplar::with(['curriculum', 'subject', 'dokLevel'])->latest()->get(),
            'candidates' => ResearchRequest::with(['classLevel.curriculum', 'subject'])
                ->where('marked_helpful', true)
                ->whereNull('promoted_to_exemplar_id')
                ->latest()
                ->get(),
            'curricula' => Curriculum::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'dokLevels' => DokLevel::orderBy('level')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'curriculum_id' => 'required|exists:curricula,id',
            'subject_id' => 'required|exists:subjects,id',
            'dok_level_id' => 'nullable|exists:dok_levels,id',
            'class_tag' => 'nullable|string|max:30',
            'title' => 'required|string|max:200',
            'material_type' => 'required|string|max:40',
            'content' => 'required|string',
        ]);

        CurriculumExemplar::create($data + [
            'approved_by_platform_admin_id' => Auth::guard('platform')->id(),
        ]);

        return redirect()->route('platform.curriculum-exemplars.index')->with('success', 'Exemplar added.');
    }

    // Turns an already-generated, teacher-approved research_request into a
    // reusable exemplar - the content and grounding it was produced from
    // (curriculum/subject/class/DOK) carry straight over, so a platform
    // admin only needs to confirm/edit the title rather than retype
    // everything.
    public function promote(Request $request, ResearchRequest $researchRequest): RedirectResponse
    {
        abort_if($researchRequest->promoted_to_exemplar_id, 422, 'Already promoted.');
        abort_unless($researchRequest->status === 'completed' && $researchRequest->generated_content, 422, 'Nothing to promote.');

        $data = $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $exemplar = CurriculumExemplar::create([
            'curriculum_id' => $researchRequest->classLevel->curriculum_id,
            'subject_id' => $researchRequest->subject_id,
            'dok_level_id' => $researchRequest->assigned_dok_level_id,
            'class_tag' => $researchRequest->classLevel->name,
            'title' => $data['title'],
            'material_type' => $researchRequest->material_type,
            'content' => $researchRequest->generated_content,
            'approved_by_platform_admin_id' => Auth::guard('platform')->id(),
        ]);

        $researchRequest->update(['promoted_to_exemplar_id' => $exemplar->id]);

        return redirect()->route('platform.curriculum-exemplars.index')->with('success', "Promoted to exemplar: \"{$exemplar->title}\".");
    }

    public function destroy(CurriculumExemplar $exemplar): RedirectResponse
    {
        $exemplar->delete();

        return back()->with('success', 'Exemplar removed.');
    }
}
