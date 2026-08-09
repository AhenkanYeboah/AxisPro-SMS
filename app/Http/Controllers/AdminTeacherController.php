<?php

namespace App\Http\Controllers;

use App\Models\ClassLevel;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * There was previously no admin-facing screen to see or fix a teacher's
 * class assignment at all - teachers self-registered once (TeacherAuthController)
 * and that was the only point their class was ever set. This exists
 * specifically to let an admin correct a teacher whose class_level_id
 * never got set (e.g. anyone who registered before class_level_id existed
 * on the signup form) without needing database access.
 */
class AdminTeacherController extends Controller
{
    public function index(): View
    {
        return view('admin.teachers', [
            // Teacher is BelongsToSchool - already scoped to the current
            // tenant.
            'teachers' => Teacher::with('classLevel.curriculum')->orderBy('full_name')->get(),
            'classLevels' => ClassLevel::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'class_level_id' => 'required|exists:class_levels,id',
        ]);

        $classLevel = ClassLevel::findOrFail($data['class_level_id']);

        $teacher->update([
            'class_level_id' => $classLevel->id,
            // Kept in sync for the same reason as at signup - see
            // TeacherAuthController::signup() - every attendance/timetable/
            // assignment/report-card screen still reads the string column.
            'assigned_class' => $classLevel->name,
        ]);

        return back()->with('success', "{$teacher->full_name}'s class updated to {$classLevel->displayName()}.");
    }
}
