<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    // Dedicated teacher assignments page (create form + list), replacing the
    // $page == 'teacher_assignments' branch. The dashboard keeps its own
    // quick-post form/list for backwards compatibility with existing routes.
    public function teacherIndex(): View
    {
        $teacher = Auth::guard('teacher')->user();
        $teacherClass = $teacher->assigned_class;

        $assignments = $teacherClass
            ? Assignment::where('class', $teacherClass)
                ->withCount('submissions')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('teacher.assignments', [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'noClassAssigned' => !$teacherClass,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = Auth::guard('teacher')->user();

        if (!$teacher->assigned_class) {
            return redirect()->back()->with('error', "You don't have a class assigned.");
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'assignment_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('assignment_file')) {
            $filePath = $request->file('assignment_file')->store('uploads/assignments', 'public');
        }

        Assignment::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'],
            'file_path' => $filePath,
            'teacher_id' => $teacher->id,
            'class' => $teacher->assigned_class,
        ]);

        return redirect()->back()->with('status', 'Assignment created successfully.');
    }

    // Original code manually re-checked "does this assignment belong to this
    // teacher?" before allowing delete. Same safety check here, just shorter.
    // Also removes the attached file, mirroring the original's unlink() call.
    public function destroy(Assignment $assignment): RedirectResponse
    {
        if ($assignment->teacher_id !== Auth::guard('teacher')->id()) {
            abort(403);
        }

        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }

        $assignment->delete();

        return redirect()->back()->with('status', 'Assignment deleted.');
    }
}
