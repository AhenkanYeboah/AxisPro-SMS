<?php

namespace App\Http\Controllers;

use App\Models\ClassLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * School-admin management of this school's class_levels. Two jobs:
 * (1) fix up backfilled classes that came through with curriculum_id =
 * null (see the backfill migration - happens when a school has zero or
 * more than one curriculum activated, since the migration can't safely
 * guess which one a given class belongs to), and (2) create genuinely
 * new classes going forward, now that "class" is a real entity rather
 * than a freetext field typed differently everywhere.
 */
class AdminClassLevelController extends Controller
{
    public function index(): View
    {
        return view('admin.class-levels', [
            // ClassLevel is BelongsToSchool - already scoped to the
            // current tenant, no explicit where('school_id') needed here.
            'classLevels' => ClassLevel::with('curriculum')->orderBy('sort_order')->orderBy('name')->get(),
            // A school can only assign curricula it has actually activated
            // at signup/settings - not the full platform list - so a class
            // can't accidentally be tagged with a curriculum the school
            // never chose to run.
            'schoolCurricula' => app('currentSchool')->curricula()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'section' => 'nullable|string|max:20',
            'curriculum_id' => 'required|exists:curricula,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->assertCurriculumActivated($data['curriculum_id']);

        ClassLevel::create([
            'name' => $data['name'],
            'section' => $data['section'] ?? null,
            'curriculum_id' => $data['curriculum_id'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', "Class \"{$data['name']}\" created.");
    }

    // Primarily used to assign a curriculum to classes the backfill
    // migration left null - see class docblock.
    public function update(Request $request, ClassLevel $classLevel): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'section' => 'nullable|string|max:20',
            'curriculum_id' => 'required|exists:curricula,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->assertCurriculumActivated($data['curriculum_id']);

        $classLevel->update([
            'name' => $data['name'],
            'section' => $data['section'] ?? null,
            'curriculum_id' => $data['curriculum_id'],
            'sort_order' => $data['sort_order'] ?? $classLevel->sort_order,
        ]);

        return back()->with('success', "Class \"{$classLevel->name}\" updated.");
    }

    public function destroy(ClassLevel $classLevel): RedirectResponse
    {
        // Students/teachers/etc. pointing at this class_level are set to
        // null on delete (nullOnDelete in every FK, see migrations) rather
        // than cascaded - deleting a class definition shouldn't delete the
        // students in it, just leave them temporarily unassigned.
        $classLevel->delete();

        return back()->with('success', 'Class removed.');
    }

    private function assertCurriculumActivated(int $curriculumId): void
    {
        $activated = app('currentSchool')->curricula()->where('curricula.id', $curriculumId)->exists();

        abort_unless($activated, 422, 'This curriculum is not activated for your school. Activate it in Settings first.');
    }
}
